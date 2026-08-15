<?php

namespace App\Livewire\Sponsors;

use App\Actions\Deal\DeleteDealAssetAction;
use App\Actions\Deal\StoreDealAssetAction;
use App\Enums\PaymentStatus;
use App\Models\Deal;
use App\Models\DealAsset;
use App\Models\Package;
use App\Models\Sponsor;
use App\Services\DealAssetArchiver;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SponsorShow extends Component
{
    use Toast, WithFileUploads;

    public Sponsor $sponsor;

    /**
     * Uploaded files keyed by deal id (each deal row has its own uploader).
     *
     * @var array<int, array<int, TemporaryUploadedFile>>
     */
    public array $dealAssets = [];

    /**
     * Optional per-file names keyed by [dealId][fileIndex].
     *
     * @var array<int, array<int, string>>
     */
    public array $dealAssetNames = [];

    public function mount(Sponsor $sponsor): void
    {
        abort_unless(auth()->user()->isJ4u(), 403);

        $this->sponsor = $sponsor;
    }

    public function uploadAssets(int $dealId): void
    {
        $this->authorizeJ4u();

        $deal = $this->sponsorDeal($dealId);

        $this->validate([
            "dealAssets.$dealId" => ['array'],
            "dealAssets.$dealId.*" => ['file', 'max:51200'], // 50 MB each
        ]);

        $uploaderId = auth()->id() !== null ? (int) auth()->id() : null;

        foreach ($this->dealAssets[$dealId] ?? [] as $i => $file) {
            app(StoreDealAssetAction::class)->execute($deal, $file, $uploaderId, $this->dealAssetNames[$dealId][$i] ?? null);
        }

        unset($this->dealAssets[$dealId], $this->dealAssetNames[$dealId]);

        $this->success(__('Assets uploaded.'));
    }

    public function deleteAsset(int $assetId): void
    {
        $this->authorizeJ4u();

        app(DeleteDealAssetAction::class)->execute($this->sponsorAsset($assetId));

        $this->success(__('Asset removed.'));
    }

    public function downloadAsset(int $assetId): StreamedResponse
    {
        $this->authorizeJ4u();

        $asset = $this->sponsorAsset($assetId);

        return Storage::disk($asset->disk)->download($asset->path, $asset->downloadName());
    }

    public function downloadAll(int $dealId): ?BinaryFileResponse
    {
        $this->authorizeJ4u();

        $deal = $this->sponsorDeal($dealId);

        $path = app(DealAssetArchiver::class)->zip($deal);

        if ($path === null) {
            $this->error(__('No assets to download.'));

            return null;
        }

        return response()->download($path, $deal->deal_number.'-assets.zip')->deleteFileAfterSend();
    }

    public function render(): View
    {
        $deals = $this->sponsor->deals()
            ->with(['package', 'items', 'paymentTerms', 'assets.uploadedBy', 'doctor'])
            ->latest()
            ->get();

        $items = $deals->flatMap->items
            ->unique('id')
            ->sortBy('name')
            ->values();

        return view('livewire.sponsors.sponsor-show', [
            'deals' => $deals,
            'items' => $items,
            'totalValue' => $deals->sum(fn (Deal $d): float => (float) $d->final_price),
            'assetsCount' => $deals->sum(fn (Deal $d): int => $d->assets->count()),
            'topPackage' => $deals->map(fn (Deal $deal): ?Package => $deal->package)->filter()
                ->sortByDesc(fn (Package $package): float => (float) $package->default_price)->first(),
            'paidStatus' => PaymentStatus::Paid,
        ]);
    }

    protected function sponsorDeal(int $dealId): Deal
    {
        $deal = Deal::findOrFail($dealId);
        abort_unless($deal->sponsor_id === $this->sponsor->id, 403);

        return $deal;
    }

    protected function sponsorAsset(int $assetId): DealAsset
    {
        $asset = DealAsset::with('deal')->findOrFail($assetId);
        abort_unless($asset->deal->sponsor_id === $this->sponsor->id, 403);

        return $asset;
    }

    protected function authorizeJ4u(): void
    {
        abort_unless(auth()->user()->isJ4u(), 403);
    }
}
