<?php

namespace App\Observers;

use App\Models\MaterialDeadline;
use App\Support\ActivityLogger;

class MaterialDeadlineObserver
{
    /**
     * Handle the MaterialDeadline "created" event.
     */
    public function created(MaterialDeadline $materialDeadline): void
    {
        ActivityLogger::log($materialDeadline->deal_id, 'material_deadline.created', $materialDeadline->getAttributes());
    }

    /**
     * Handle the MaterialDeadline "updated" event.
     */
    public function updated(MaterialDeadline $materialDeadline): void
    {
        $changes = ActivityLogger::changes($materialDeadline);

        if ($changes !== []) {
            ActivityLogger::log($materialDeadline->deal_id, 'material_deadline.updated', $changes);
        }
    }
}
