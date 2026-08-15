<?php

namespace App\Livewire;

use App\Actions\Deal\DeleteDealAssetAction;
use App\Actions\Deal\DeletePaymentProofAction;
use App\Actions\Deal\FinalizeDealAction;
use App\Actions\Deal\MarkMaterialReceivedAction;
use App\Actions\Deal\MarkPaymentTermPaidAction;
use App\Actions\Deal\StoreDealAssetAction;
use App\Actions\Deal\UploadPaymentProofAction;
use App\Enums\MaterialStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\QuotaExceededException;
use App\Models\Deal;
use App\Models\DealAsset;
use App\Models\MaterialDeadline;
use App\Models\PaymentTerm;
use App\Services\DealAssetArchiver;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DealShow extends Component
{
    use Toast, WithFileUploads;

    public Deal $deal;

    /** @var array<int, TemporaryUploadedFile> */
    public array $assets = [];

    /** @var array<int, string> Optional per-file names, keyed by index. */
    public array $assetNames = [];

    /** @var array<int, TemporaryUploadedFile> Transfer proof uploads, keyed by payment term id. */
    public array $proofUploads = [];

    public function mount(Deal $deal): void
    {
        abort_unless(auth()->user()->isJ4u(), 403);

        $this->deal = $deal->load([
            'sponsor',
            'doctor',
            'package',
            'items',
            'paymentTerms',
            'materialDeadlines.item',
            'assets.uploadedBy',
            'activityLogs.user',
        ]);
    }

    public function finalize(): void
    {
        $this->authorizeJ4u();

        try {
            app(FinalizeDealAction::class)->execute($this->deal);
        } catch (QuotaExceededException $e) {
            $this->error($e->getMessage());

            return;
        }

        $this->deal->refresh();

        $this->success(__('Deal finalized — material checklist generated.'));
    }

    public function uploadAssets(): void
    {
        $this->authorizeJ4u();

        $this->validate([
            'assets' => ['array'],
            'assets.*' => ['file', 'max:51200'], // 50 MB each
            'assetNames.*' => ['nullable', 'string', 'max:255'],
        ]);

        $uploaderId = auth()->id() !== null ? (int) auth()->id() : null;

        foreach ($this->assets as $i => $file) {
            app(StoreDealAssetAction::class)->execute($this->deal, $file, $uploaderId, $this->assetNames[$i] ?? null);
        }

        $this->assets = [];
        $this->assetNames = [];
        $this->deal->load('assets.uploadedBy');

        $this->success(__('Assets uploaded.'));
    }

    public function deleteAsset(int $assetId): void
    {
        $this->authorizeJ4u();

        $asset = DealAsset::findOrFail($assetId);
        abort_unless($asset->deal_id === $this->deal->id, 403);

        app(DeleteDealAssetAction::class)->execute($asset);

        $this->deal->load('assets.uploadedBy');

        $this->success(__('Asset removed.'));
    }

    public function downloadAsset(int $assetId): StreamedResponse
    {
        $this->authorizeJ4u();

        $asset = DealAsset::findOrFail($assetId);
        abort_unless($asset->deal_id === $this->deal->id, 403);

        return Storage::disk($asset->disk)->download($asset->path, $asset->downloadName());
    }

    public function downloadAll(): ?BinaryFileResponse
    {
        $this->authorizeJ4u();

        $path = app(DealAssetArchiver::class)->zip($this->deal);

        if ($path === null) {
            $this->error(__('No assets to download.'));

            return null;
        }

        return response()->download($path, $this->deal->deal_number.'-assets.zip')->deleteFileAfterSend();
    }

    public function markPaymentPaid(int $paymentTermId): void
    {
        $this->authorizeJ4u();

        $term = PaymentTerm::findOrFail($paymentTermId);
        abort_unless($term->deal_id === $this->deal->id, 403);

        app(MarkPaymentTermPaidAction::class)->execute($term);

        $this->deal->refresh();
    }

    public function uploadProof(int $paymentTermId): void
    {
        $this->authorizeJ4u();

        $term = $this->dealPaymentTerm($paymentTermId);

        $this->validate([
            "proofUploads.$paymentTermId" => ['required', 'file', 'max:51200', 'mimes:jpg,jpeg,png,webp,pdf'],
        ]);

        app(UploadPaymentProofAction::class)->execute($term, $this->proofUploads[$paymentTermId]);

        unset($this->proofUploads[$paymentTermId]);
        $this->deal->load('paymentTerms');

        $this->success(__('Transfer proof uploaded.'));
    }

    public function deleteProof(int $paymentTermId): void
    {
        $this->authorizeJ4u();

        app(DeletePaymentProofAction::class)->execute($this->dealPaymentTerm($paymentTermId));

        $this->deal->load('paymentTerms');

        $this->success(__('Transfer proof removed.'));
    }

    public function downloadProof(int $paymentTermId): StreamedResponse
    {
        $this->authorizeJ4u();

        $term = $this->dealPaymentTerm($paymentTermId);
        abort_unless($term->proof_path !== null, 404);

        return Storage::disk($term->proof_disk ?? 'public')->download($term->proof_path, $term->proofDownloadName());
    }

    public function markMaterialReceived(int $materialDeadlineId): void
    {
        $this->authorizeJ4u();

        $deadline = MaterialDeadline::findOrFail($materialDeadlineId);
        abort_unless($deadline->deal_id === $this->deal->id, 403);

        app(MarkMaterialReceivedAction::class)->execute($deadline);

        $this->deal->refresh();
    }

    public function render(): View
    {
        $paymentTerms = $this->deal->paymentTerms;
        $materialDeadlines = $this->deal->materialDeadlines;

        return view('livewire.deal-show', [
            'totalPaid' => $paymentTerms->where('status', PaymentStatus::Paid)->sum('amount'),
            'totalTerms' => $paymentTerms->sum('amount'),
            'materialReceived' => $materialDeadlines->where('status', MaterialStatus::Received)->count(),
            'materialCount' => $materialDeadlines->count(),
        ]);
    }

    protected function authorizeJ4u(): void
    {
        abort_unless(auth()->user()->isJ4u(), 403);
    }

    protected function dealPaymentTerm(int $paymentTermId): PaymentTerm
    {
        $term = PaymentTerm::findOrFail($paymentTermId);
        abort_unless($term->deal_id === $this->deal->id, 403);

        return $term;
    }
}
