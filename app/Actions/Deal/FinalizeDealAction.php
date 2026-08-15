<?php

namespace App\Actions\Deal;

use App\Enums\DealStatus;
use App\Exceptions\QuotaExceededException;
use App\Models\Deal;
use App\Services\QuotaService;
use Illuminate\Support\Facades\DB;

class FinalizeDealAction
{
    public function __construct(private readonly QuotaService $quota) {}

    /**
     * Finalize a deal. The DealObserver fires DealFinalized,
     * which generates the material checklist.
     *
     * @throws QuotaExceededException when finalizing would exceed a quota.
     */
    public function execute(Deal $deal): Deal
    {
        return DB::transaction(function () use ($deal): Deal {
            $this->assertQuotaAvailable($deal);

            $deal->update(['status' => DealStatus::Finalized]);

            return $deal;
        });
    }

    /**
     * Authoritative quota guard: two drafts can both hold the last slot, but
     * only one may finalize into it.
     */
    protected function assertQuotaAvailable(Deal $deal): void
    {
        $deal->loadMissing(['package', 'items']);

        if ($deal->package !== null && $deal->package->quota !== null) {
            $taken = $this->quota->packageTakenCount($deal->package->id, $deal->id);

            if ($taken + 1 > $deal->package->quota) {
                throw new QuotaExceededException(
                    __('Package ":name" is at full quota (:quota).', [
                        'name' => $deal->package->name,
                        'quota' => $deal->package->quota,
                    ])
                );
            }
        }

        foreach ($deal->items as $item) {
            if ($item->quota === null) {
                continue;
            }

            $taken = $this->quota->itemTakenCount($item->id, $deal->id);

            if ($taken + 1 > $item->quota) {
                throw new QuotaExceededException(
                    __('Item ":name" is at full quota (:quota).', [
                        'name' => $item->name,
                        'quota' => $item->quota,
                    ])
                );
            }
        }
    }
}
