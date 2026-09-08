<?php

namespace App\Actions\GuaranteeLetter;

use App\Enums\GuaranteeLetterStatus;
use App\Models\GuaranteeLetter;
use App\Models\PaymentTerm;
use App\Support\ActivityLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SaveGuaranteeLetterAction
{
    /**
     * Step 1: attach the guarantee letter document to a payment term. Creates
     * the record on first save; re-saving replaces the document. The letter
     * guarantees the term's own amount, so no figure is stored here.
     */
    public function execute(PaymentTerm $paymentTerm, UploadedFile $document): GuaranteeLetter
    {
        return DB::transaction(function () use ($paymentTerm, $document): GuaranteeLetter {
            $letter = $paymentTerm->guaranteeLetter()->firstOrNew([]);
            $isNew = ! $letter->exists;

            if ($isNew) {
                $letter->status = GuaranteeLetterStatus::Uploaded;
            }

            if ($letter->doc_path !== null) {
                Storage::disk($letter->doc_disk ?? 'public')->delete($letter->doc_path);
            }

            $path = $document->store("guarantee-letters/{$paymentTerm->deal_id}/{$paymentTerm->id}", 'public');

            $letter->fill([
                'doc_disk' => 'public',
                'doc_path' => $path,
                'doc_original_name' => $document->getClientOriginalName(),
                'doc_size' => $document->getSize(),
            ]);

            $paymentTerm->guaranteeLetter()->save($letter);

            ActivityLogger::log(
                $paymentTerm->deal_id,
                $isNew ? 'guarantee_letter.created' : 'guarantee_letter.updated',
                [
                    'term' => ['old' => null, 'new' => $paymentTerm->description],
                    'document' => ['old' => null, 'new' => $document->getClientOriginalName()],
                ]
            );

            return $letter;
        });
    }
}
