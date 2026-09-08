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
 * @property bool $is_addon
 * @property string|null $custom_price
 */
class DealItem extends Pivot
{
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
