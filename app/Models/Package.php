<?php

namespace App\Models;

use Database\Factories\PackageFactory;
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
 * @property numeric-string|null $default_price_idr
 * @property numeric-string|null $default_price_usd
 * @property int|null $quota
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'default_price_idr', 'default_price_usd', 'quota'])]
class Package extends Model
{
    /** @use HasFactory<PackageFactory> */
    use HasFactory;

    /** @return BelongsToMany<Item, $this, Pivot> */
    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'package_item')->withPivot('quantity')->withTimestamps();
    }

    /** @return HasMany<Deal, $this> */
    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }

    protected function casts(): array
    {
        return [
            'default_price_idr' => 'decimal:2',
            'default_price_usd' => 'decimal:2',
            'quota' => 'integer',
        ];
    }
}
