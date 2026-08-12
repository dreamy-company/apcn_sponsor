<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Pivot for deal_items with typed casts.
 *
 * @property int $deal_id
 * @property int $item_id
 * @property bool $is_addon
 * @property string|null $custom_price
 */
class DealItem extends Pivot
{
    protected function casts(): array
    {
        return [
            'is_addon' => 'boolean',
            'custom_price' => 'decimal:2',
        ];
    }
}
