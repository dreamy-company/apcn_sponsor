<?php

namespace App\Actions\Deal;

use App\Enums\MaterialStatus;
use App\Models\MaterialDeadline;
use Illuminate\Support\Facades\DB;

class MarkMaterialReceivedAction
{
    /**
     * Mark a material deadline as received.
     */
    public function execute(MaterialDeadline $materialDeadline): MaterialDeadline
    {
        return DB::transaction(function () use ($materialDeadline): MaterialDeadline {
            $materialDeadline->update([
                'status' => MaterialStatus::Received,
                'received_at' => now(),
            ]);

            return $materialDeadline;
        });
    }
}
