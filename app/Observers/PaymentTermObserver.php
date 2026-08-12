<?php

namespace App\Observers;

use App\Models\PaymentTerm;
use App\Support\ActivityLogger;

class PaymentTermObserver
{
    /**
     * Handle the PaymentTerm "created" event.
     */
    public function created(PaymentTerm $paymentTerm): void
    {
        ActivityLogger::log($paymentTerm->deal_id, 'payment_term.created', $paymentTerm->getAttributes());
    }

    /**
     * Handle the PaymentTerm "updated" event.
     */
    public function updated(PaymentTerm $paymentTerm): void
    {
        $changes = ActivityLogger::changes($paymentTerm);

        if ($changes !== []) {
            ActivityLogger::log($paymentTerm->deal_id, 'payment_term.updated', $changes);
        }
    }
}
