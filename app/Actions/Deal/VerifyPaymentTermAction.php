<?php

namespace App\Actions\Deal;

use App\Models\PaymentTerm;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Support\Facades\DB;

class VerifyPaymentTermAction
{
    /**
     * Record that J4U has checked a term's transfer proof against its amount.
     * Passing $verified = false clears the verification.
     */
    public function execute(PaymentTerm $paymentTerm, User $actor, bool $verified = true): PaymentTerm
    {
        return DB::transaction(function () use ($paymentTerm, $actor, $verified): PaymentTerm {
            $paymentTerm->updateQuietly([
                'verified_at' => $verified ? now() : null,
                'verified_by_id' => $verified ? $actor->id : null,
            ]);

            ActivityLogger::log(
                $paymentTerm->deal_id,
                $verified ? 'payment_term.verified' : 'payment_term.unverified',
                ['description' => ['old' => null, 'new' => $paymentTerm->description]],
            );

            return $paymentTerm;
        });
    }
}
