<?php

namespace App\Services;

use App\Enums\DealStatus;
use App\Enums\PaymentStatus;
use App\Models\Deal;
use App\Models\Item;
use App\Models\Package;
use App\Models\Sponsor;
use Illuminate\Support\Collection;

/**
 * Aggregates the global sponsorship picture for the public report page.
 */
class SponsorshipReportService
{
    public function __construct(private readonly DashboardService $dashboard) {}

    /**
     * @return array{
     *     summary: array<string, mixed>,
     *     sponsorsCount: int,
     *     sponsors: Collection<int, Sponsor>,
     *     finalizedDeals: Collection<int, Deal>,
     *     draftDeals: Collection<int, Deal>,
     *     packageUptake: Collection<int, Package>,
     *     itemUptake: Collection<int, Item>,
     * }
     */
    public function report(): array
    {
        return [
            'summary' => $this->dashboard->summary(),
            'sponsorsCount' => Sponsor::count(),
            'sponsors' => Sponsor::query()
                ->has('deals')
                ->with('deals.package')
                ->withCount('deals')
                ->withSum('deals', 'final_price')
                ->orderByDesc('deals_sum_final_price')
                ->orderBy('company_name')
                ->get(),
            'finalizedDeals' => $this->deals(DealStatus::Finalized),
            'draftDeals' => $this->deals(DealStatus::Draft),
            'packageUptake' => Package::query()
                ->withCount(['deals as taken_count' => fn ($q) => $q->where('status', DealStatus::Finalized->value)])
                ->orderByDesc('default_price')
                ->get(),
            'itemUptake' => Item::query()
                ->whereNotNull('quota')
                ->withCount(['deals as taken_count' => fn ($q) => $q->where('deals.status', DealStatus::Finalized->value)])
                ->orderBy('name')
                ->get(),
        ];
    }

    /**
     * @return Collection<int, Deal>
     */
    private function deals(DealStatus $status): Collection
    {
        return Deal::query()
            ->where('status', $status)
            ->with(['sponsor', 'package'])
            ->withSum(['paymentTerms as paid_total' => fn ($q) => $q->where('status', PaymentStatus::Paid)], 'amount')
            ->latest()
            ->get();
    }
}
