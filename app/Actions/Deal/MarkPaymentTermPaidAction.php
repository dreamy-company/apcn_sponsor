<?php

namespace App\Actions\Deal;

use App\Enums\PaymentStatus;
use App\Models\PaymentTerm;
use Illuminate\Support\Facades\DB;

class MarkPaymentTermPaidAction
{
    /**
     * Mark a payment term as paid.
     */
    public function execute(PaymentTerm $paymentTerm): PaymentTerm
    {
        return DB::transaction(function () use ($paymentTerm): PaymentTerm {
            $paymentTerm->update(['status' => PaymentStatus::Paid]);

            return $paymentTerm;
        });
    }
}
