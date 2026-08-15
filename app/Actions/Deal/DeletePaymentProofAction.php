<?php

namespace App\Actions\Deal;

use App\Models\PaymentTerm;
use App\Support\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeletePaymentProofAction
{
    /**
     * Remove a payment term's transfer proof (file + columns).
     */
    public function execute(PaymentTerm $paymentTerm): void
    {
        DB::transaction(function () use ($paymentTerm): void {
            if ($paymentTerm->proof_path !== null) {
                Storage::disk($paymentTerm->proof_disk ?? 'public')->delete($paymentTerm->proof_path);
            }

            $paymentTerm->updateQuietly([
                'proof_disk' => null,
                'proof_path' => null,
                'proof_original_name' => null,
                'proof_size' => null,
            ]);

            ActivityLogger::log($paymentTerm->deal_id, 'payment_term.proof_removed');
        });
    }
}
