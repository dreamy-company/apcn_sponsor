<?php

namespace App\Livewire;

use App\Actions\Deal\DeleteDealAssetAction;
use App\Actions\Deal\DeletePaymentProofAction;
use App\Actions\Deal\FinalizeDealAction;
use App\Actions\Deal\MarkMaterialReceivedAction;
use App\Actions\Deal\MarkPaymentTermPaidAction;
use App\Actions\Deal\StoreDealAssetAction;
use App\Actions\Deal\UploadPaymentProofAction;
use App\Actions\Deal\VerifyPaymentTermAction;
use App\Actions\GuaranteeLetter\SaveGuaranteeLetterAction;
use App\Actions\GuaranteeLetter\ScheduleGuaranteeLetterPaymentAction;
use App\Actions\GuaranteeLetter\UploadGuaranteeLetterProofAction;
use App\Actions\GuaranteeLetter\VerifyGuaranteeLetterAction;
use App\Enums\MaterialStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\QuotaExceededException;
use App\Exceptions\UnbalancedPaymentTermsException;
use App\Models\Deal;
use App\Models\DealAsset;
use App\Models\GuaranteeLetter;
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

    /** @var array<int, TemporaryUploadedFile> Guarantee letter documents, keyed by payment term id. */
    public array $glDocuments = [];

    /** @var array<int, string> Guarantee letter payment dates, keyed by payment term id. */
    public array $glDueDates = [];

    /** @var array<int, TemporaryUploadedFile> Guarantee letter transfer proofs, keyed by payment term id. */
    public array $glProofs = [];

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
            'paymentTerms.guaranteeLetter.verifiedBy',
        ]);

        foreach ($this->deal->paymentTerms as $term) {
            $this->glDueDates[$term->id] = $term->guaranteeLetter?->payment_due_date?->format('Y-m-d') ?? '';
        }
    }

    public function finalize(): void
    {
        $this->authorizeJ4u();

        try {
            app(FinalizeDealAction::class)->execute($this->deal);
        } catch (QuotaExceededException|UnbalancedPaymentTermsException $e) {
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

    public function verifyPaymentTerm(int $paymentTermId, bool $verified = true): void
    {
        $this->authorizeJ4u();

        $term = $this->dealPaymentTerm($paymentTermId);

        // Verification means "the proof matches the amount" — there must be a proof.
        if ($verified && ! $term->hasProof()) {
            $this->error(__('Upload the transfer proof before verifying this term.'));

            return;
        }

        app(VerifyPaymentTermAction::class)->execute($term, auth()->user(), $verified);

        $this->deal->load('paymentTerms');

        $this->success($verified ? __('Payment verified.') : __('Verification cleared.'));
    }

    // ---------------------------------------------------------- Guarantee letter

    /**
     * Step 1: attach the guarantee letter that will settle this term.
     */
    public function saveGuaranteeLetter(int $paymentTermId): void
    {
        $this->authorizeJ4u();

        $term = $this->dealPaymentTerm($paymentTermId);

        $this->validate([
            "glDocuments.$paymentTermId" => ['required', 'file', 'max:51200', 'mimes:jpg,jpeg,png,webp,pdf'],
        ]);

        app(SaveGuaranteeLetterAction::class)->execute($term, $this->glDocuments[$paymentTermId]);

        unset($this->glDocuments[$paymentTermId]);
        $this->refreshPaymentTerms();

        $this->success(__('Guarantee letter saved.'));
    }

    /**
     * Step 2: set the date the guaranteed amount will be paid.
     */
    public function scheduleGuaranteeLetter(int $paymentTermId): void
    {
        $this->authorizeJ4u();

        $letter = $this->termGuaranteeLetter($paymentTermId);

        if ($letter === null) {
            $this->error(__('Upload the guarantee letter first.'));

            return;
        }

        $this->validate(["glDueDates.$paymentTermId" => ['required', 'date']]);

        app(ScheduleGuaranteeLetterPaymentAction::class)
            ->execute($letter, $this->glDueDates[$paymentTermId]);

        $this->refreshPaymentTerms();

        $this->success(__('Payment date set.'));
    }

    /**
     * Step 3: on the payment date, attach the transfer proof.
     */
    public function uploadGuaranteeLetterProof(int $paymentTermId): void
    {
        $this->authorizeJ4u();

        $letter = $this->termGuaranteeLetter($paymentTermId);

        if ($letter === null || $letter->payment_due_date === null) {
            $this->error(__('Set the guarantee letter payment date first.'));

            return;
        }

        $this->validate([
            "glProofs.$paymentTermId" => ['required', 'file', 'max:51200', 'mimes:jpg,jpeg,png,webp,pdf'],
        ]);

        app(UploadGuaranteeLetterProofAction::class)->execute($letter, $this->glProofs[$paymentTermId]);

        unset($this->glProofs[$paymentTermId]);
        $this->refreshPaymentTerms();

        $this->success(__('Guarantee letter payment proof uploaded.'));
    }

    public function verifyGuaranteeLetter(int $paymentTermId, bool $verified = true): void
    {
        $this->authorizeJ4u();

        $letter = $this->termGuaranteeLetter($paymentTermId);

        if ($letter === null || ($verified && ! $letter->hasProof())) {
            $this->error(__('Upload the transfer proof before verifying.'));

            return;
        }

        app(VerifyGuaranteeLetterAction::class)->execute($letter, auth()->user(), $verified);

        $this->refreshPaymentTerms();

        $this->success($verified ? __('Guarantee letter verified.') : __('Verification cleared.'));
    }

    public function downloadGuaranteeLetter(int $paymentTermId, string $which = 'document'): StreamedResponse
    {
        $this->authorizeJ4u();

        $letter = $this->termGuaranteeLetter($paymentTermId);
        abort_if($letter === null, 404);

        [$disk, $path, $name] = $which === 'proof'
            ? [$letter->proof_disk, $letter->proof_path, $letter->proofDownloadName()]
            : [$letter->doc_disk, $letter->doc_path, $letter->documentDownloadName()];

        abort_if($path === null, 404);

        return Storage::disk($disk ?? 'public')->download($path, $name);
    }

    /**
     * The guarantee letter on one of this deal's terms (null when the term is
     * settled by a direct transfer instead).
     */
    protected function termGuaranteeLetter(int $paymentTermId): ?GuaranteeLetter
    {
        return $this->dealPaymentTerm($paymentTermId)->guaranteeLetter;
    }

    protected function refreshPaymentTerms(): void
    {
        $this->deal->load('paymentTerms.guaranteeLetter.verifiedBy');
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

        // A guarantee letter settles a term rather than adding money of its own,
        // so payment progress is simply the terms (BR-09).
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
