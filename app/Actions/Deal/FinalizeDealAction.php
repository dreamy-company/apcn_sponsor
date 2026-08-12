<?php

namespace App\Actions\Deal;

use App\Enums\DealStatus;
use App\Models\Deal;
use Illuminate\Support\Facades\DB;

class FinalizeDealAction
{
    /**
     * Finalize a deal. The DealObserver fires DealFinalized,
     * which generates the material checklist.
     */
    public function execute(Deal $deal): Deal
    {
        return DB::transaction(function () use ($deal): Deal {
            $deal->update(['status' => DealStatus::Finalized]);

            return $deal;
        });
    }
}
