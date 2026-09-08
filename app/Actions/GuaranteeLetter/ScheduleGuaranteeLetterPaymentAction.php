<?php

namespace App\Actions\GuaranteeLetter;

use App\Enums\GuaranteeLetterStatus;
use App\Models\GuaranteeLetter;
use App\Support\ActivityLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ScheduleGuaranteeLetterPaymentAction
{
    /**
     * Step 2: set the date on which the guaranteed amount will be paid.
     * Does not move a letter that is already paid back to scheduled.
     */
    public function execute(GuaranteeLetter $letter, string $dueDate): GuaranteeLetter
    {
        return DB::transaction(function () use ($letter, $dueDate): GuaranteeLetter {
            $previous = $letter->payment_due_date?->toDateString();

            $letter->payment_due_date = Carbon::parse($dueDate);

            if ($letter->status === GuaranteeLetterStatus::Uploaded) {
                $letter->status = GuaranteeLetterStatus::Scheduled;
            }

            $letter->save();

            ActivityLogger::log($letter->paymentTerm->deal_id, 'guarantee_letter.scheduled', [
                'payment_due_date' => ['old' => $previous, 'new' => $dueDate],
            ]);

            return $letter;
        });
    }
}
