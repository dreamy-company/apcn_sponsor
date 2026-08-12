<?php

namespace App\Listeners;

use App\Enums\MaterialStatus;
use App\Events\DealFinalized;
use App\Models\MaterialDeadline;

class GenerateMaterialDeadlines
{
    /**
     * Generate the material checklist for a finalized deal:
     * one MaterialDeadline per deal item that requires material.
     */
    public function handle(DealFinalized $event): void
    {
        $deal = $event->deal->load('items');

        foreach ($deal->items as $item) {
            if (! $item->requires_material) {
                continue;
            }

            $exists = $deal->materialDeadlines()->where('item_id', $item->id)->exists();

            if ($exists) {
                continue;
            }

            MaterialDeadline::create([
                'deal_id' => $deal->id,
                'item_id' => $item->id,
                'material_name' => $item->name,
                'due_date' => null,
                'status' => MaterialStatus::Pending,
            ]);
        }
    }
}
