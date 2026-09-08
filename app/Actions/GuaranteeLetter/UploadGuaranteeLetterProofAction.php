<?php

namespace App\Actions\GuaranteeLetter;

use App\Enums\GuaranteeLetterStatus;
use App\Models\GuaranteeLetter;
use App\Support\ActivityLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UploadGuaranteeLetterProofAction
{
    /**
     * Step 3: on the payment date, attach the transfer proof for the guaranteed
     * amount. This settles the letter. Any prior verification is cleared, since
     * a replaced proof has not been checked yet.
     */
    public function execute(GuaranteeLetter $letter, UploadedFile $file): GuaranteeLetter
    {
        return DB::transaction(function () use ($letter, $file): GuaranteeLetter {
            if ($letter->proof_path !== null) {
                Storage::disk($letter->proof_disk ?? 'public')->delete($letter->proof_path);
            }

            $term = $letter->paymentTerm;

            $path = $file->store("guarantee-letters/{$term->deal_id}/{$term->id}/proof", 'public');

            $letter->forceFill([
                'proof_disk' => 'public',
                'proof_path' => $path,
                'proof_original_name' => $file->getClientOriginalName(),
                'proof_size' => $file->getSize(),
                'status' => GuaranteeLetterStatus::Paid,
                'verified_at' => null,
                'verified_by_id' => null,
            ])->save();

            ActivityLogger::log($term->deal_id, 'guarantee_letter.proof_uploaded', [
                'file' => ['old' => null, 'new' => $file->getClientOriginalName()],
            ]);

            return $letter;
        });
    }
}
