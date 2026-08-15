<?php

namespace App\Services;

use App\Enums\DealStatus;
use App\Models\Deal;

/**
 * Quota consumption is counted from FINALIZED deals only — drafts do not
 * reserve a slot (product decision). A null quota means unlimited.
 */
class QuotaService
{
    /**
     * How many finalized deals include the given item.
     */
    public function itemTakenCount(int $itemId, ?int $excludeDealId = null): int
    {
        return Deal::query()
            ->where('status', DealStatus::Finalized)
            ->when($excludeDealId !== null, fn ($q) => $q->whereKeyNot($excludeDealId))
            ->whereHas('items', fn ($q) => $q->where('items.id', $itemId))
            ->count();
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
