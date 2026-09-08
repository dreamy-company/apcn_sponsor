<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Pivot for deal_items with typed casts.
 *
 * @property int $deal_id
 * @property int $item_id
 * @property int $quantity
 * @property string|null $inclusion
 * @property bool $is_addon
 * @property string|null $custom_price
 */
class DealItem extends Pivot
{
    /**
     * What the sponsor gets for this item on this deal: the per-deal override
     * when one was written, otherwise the item's catalog inclusion.
     */
    public function effectiveInclusion(): ?string
    {
        $override = $this->inclusion;

        if ($override !== null && trim($override) !== '') {
            return $override;
        }

        return $this->item?->inclusion;
    }

    /** @return BelongsTo<Item, $this> */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'is_addon' => 'boolean',
            'custom_price' => 'decimal:2',
        ];
    }
}
