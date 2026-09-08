<?php

namespace App\Models;

use Database\Factories\ItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $type
 * @property string|null $inclusion
 * @property int|null $quota
 * @property numeric-string|null $default_price_idr
 * @property numeric-string|null $default_price_usd
 * @property bool $requires_material
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'type', 'inclusion', 'quota', 'default_price_idr', 'default_price_usd', 'requires_material'])]
class Item extends Model
{
    /** @use HasFactory<ItemFactory> */
    use HasFactory;

    /** @return BelongsToMany<Package, $this, Pivot> */
    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(Package::class, 'package_item')->withPivot('quantity')->withTimestamps();
    }

    /** @return BelongsToMany<Deal, $this, DealItem> */
    public function deals(): BelongsToMany
    {
        return $this->belongsToMany(Deal::class, 'deal_items')
            ->using(DealItem::class)
            ->withPivot(['quantity', 'is_addon', 'custom_price'])
            ->withTimestamps();
    }

    /** @return HasMany<MaterialDeadline, $this> */
    public function materialDeadlines(): HasMany
    {
        return $this->hasMany(MaterialDeadline::class);
    }

    protected function casts(): array
    {
        return [
            'quota' => 'integer',
            'default_price_idr' => 'decimal:2',
            'default_price_usd' => 'decimal:2',
            'requires_material' => 'boolean',
        ];
    }
}
