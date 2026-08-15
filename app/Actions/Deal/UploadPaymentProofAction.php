<?php

namespace App\Actions\Deal;

use App\Models\PaymentTerm;
use App\Support\ActivityLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UploadPaymentProofAction
{
    /**
     * Store a transfer proof for a payment term, replacing any existing file.
     */
    public function execute(PaymentTerm $paymentTerm, UploadedFile $file): PaymentTerm
    {
        return DB::transaction(function () use ($paymentTerm, $file): PaymentTerm {
            if ($paymentTerm->proof_path !== null) {
                Storage::disk($paymentTerm->proof_disk ?? 'public')->delete($paymentTerm->proof_path);
            }

            $path = $file->store("payment-proofs/{$paymentTerm->deal_id}", 'public');

            // Quietly update the file columns, then log a single clean entry
            // (avoids the observer dumping raw path strings into the audit trail).
            $paymentTerm->updateQuietly([
                'proof_disk' => 'public',
                'proof_path' => $path,
                'proof_original_name' => $file->getClientOriginalName(),
                'proof_size' => $file->getSize(),
            ]);

            ActivityLogger::log($paymentTerm->deal_id, 'payment_term.proof_uploaded', [
                'file' => ['old' => null, 'new' => $file->getClientOriginalName()],
            ]);

            return $paymentTerm;
        });
    }
}
