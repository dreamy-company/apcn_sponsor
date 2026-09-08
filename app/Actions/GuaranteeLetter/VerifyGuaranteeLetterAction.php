<?php

namespace App\Actions\GuaranteeLetter;

use App\Models\GuaranteeLetter;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Support\Facades\DB;

class VerifyGuaranteeLetterAction
{
    /**
     * Record that J4U has checked the uploaded transfer proof against the
     * guaranteed amount. Passing $verified = false clears the verification.
     */
    public function execute(GuaranteeLetter $letter, User $actor, bool $verified = true): GuaranteeLetter
    {
        return DB::transaction(function () use ($letter, $actor, $verified): GuaranteeLetter {
            $letter->forceFill([
                'verified_at' => $verified ? now() : null,
                'verified_by_id' => $verified ? $actor->id : null,
            ])->save();

            ActivityLogger::log(
                $letter->paymentTerm->deal_id,
                $verified ? 'guarantee_letter.verified' : 'guarantee_letter.unverified',
            );

            return $letter;
        });
    }
}
