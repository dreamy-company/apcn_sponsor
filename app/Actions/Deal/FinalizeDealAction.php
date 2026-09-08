<?php

namespace App\Actions\Deal;

use App\Enums\DealStatus;
use App\Exceptions\QuotaExceededException;
use App\Exceptions\UnbalancedPaymentTermsException;
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
     * @throws UnbalancedPaymentTermsException when terms do not sum to the final price.
     */
    public function execute(Deal $deal): Deal
    {
        return DB::transaction(function () use ($deal): Deal {
            $this->assertQuotaAvailable($deal);
            $this->assertPaymentTermsBalanced($deal);

            $deal->update(['status' => DealStatus::Finalized]);

            return $deal;
        });
    }

    /**
     * BR-08: payment terms must account for the whole agreed final price before a
     * deal is confirmed. The guarantee letter is a separate instrument and is
     * deliberately excluded from this sum (BR-09).
     */
    protected function assertPaymentTermsBalanced(Deal $deal): void
    {
        $deal->loadMissing('paymentTerms');

        $total = $deal->paymentTerms->sum(fn ($term): float => (float) $term->amount);
        $finalPrice = (float) $deal->final_price;

        // Money is DECIMAL(15,2); compare at cent precision to avoid float drift.
        if (abs($total - $finalPrice) >= 0.005) {
            throw new UnbalancedPaymentTermsException(
                __('Payment terms total :total but the final price is :final. They must match before finalizing.', [
                    'total' => $deal->currency->format($total),
                    'final' => $deal->currency->format($finalPrice),
                ])
            );
        }
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

            // Quota is measured in units: this deal may itself take several.
            $units = max(1, (int) $item->getAttribute('pivot')->quantity);
            $taken = $this->quota->itemTakenCount($item->id, $deal->id);

            if ($taken + $units > $item->quota) {
                throw new QuotaExceededException(
                    __('Item ":name" needs :units of :remaining remaining unit(s) (quota :quota).', [
                        'name' => $item->name,
                        'units' => $units,
                        'remaining' => max(0, $item->quota - $taken),
                        'quota' => $item->quota,
                    ])
                );
            }
        }
    }
}
