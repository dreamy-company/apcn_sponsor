<?php

namespace App\Services;

use App\Enums\DealStatus;
use App\Models\Deal;
use Illuminate\Support\Facades\DB;

/**
 * Quota consumption is counted from FINALIZED deals only — drafts do not
 * reserve a slot (product decision). A null quota means unlimited.
 *
 * Item quota is measured in UNITS, not deals: a Diamond tier bundling 5 booths
 * consumes 5 of the venue's 122, not 1. Package quota stays a sponsor-slot count,
 * since a deal has at most one base tier.
 */
class QuotaService
{
    /**
     * How many units of the given item finalized deals have taken.
     */
    public function itemTakenCount(int $itemId, ?int $excludeDealId = null): int
    {
        return (int) DB::table('deal_items')
            ->join('deals', 'deals.id', '=', 'deal_items.deal_id')
            ->where('deal_items.item_id', $itemId)
            ->where('deals.status', DealStatus::Finalized->value)
            ->when($excludeDealId !== null, fn ($q) => $q->where('deals.id', '!=', $excludeDealId))
            ->sum('deal_items.quantity');
    }

    /**
     * Units of the given item held by one deal (0 when it does not include it).
     */
    public function itemUnitsOnDeal(int $dealId, int $itemId): int
    {
        return (int) DB::table('deal_items')
            ->where('deal_id', $dealId)
            ->where('item_id', $itemId)
            ->sum('quantity');
    }

    /**
     * How many finalized deals use the given package as their base tier.
     */
    public function packageTakenCount(int $packageId, ?int $excludeDealId = null): int
    {
        return Deal::query()
            ->where('status', DealStatus::Finalized)
            ->when($excludeDealId !== null, fn ($q) => $q->whereKeyNot($excludeDealId))
            ->where('package_id', $packageId)
            ->count();
    }

    /**
     * An item/package is full when it has a quota and finalized deals have
     * reached it.
     */
    public function isFull(?int $quota, int $taken): bool
    {
        return $quota !== null && $taken >= $quota;
    }
}
