<?php

namespace App\Observers;

use App\Enums\DealStatus;
use App\Events\DealFinalized;
use App\Models\Deal;
use App\Support\ActivityLogger;

class DealObserver
{
    /**
     * Handle the Deal "created" event.
     */
    public function created(Deal $deal): void
    {
        ActivityLogger::log($deal->id, 'deal.created', $deal->getAttributes());
    }

    /**
     * Handle the Deal "updated" event.
     */
    public function updated(Deal $deal): void
    {
        $changes = ActivityLogger::changes($deal);

        if ($changes !== []) {
            ActivityLogger::log($deal->id, 'deal.updated', $changes);
        }

        if ($deal->wasChanged('status') && $deal->status === DealStatus::Finalized) {
            event(new DealFinalized($deal));
        }
    }
}
