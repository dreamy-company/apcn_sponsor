<?php

namespace App\Models;

use App\Enums\DealStatus;
use App\Observers\DealObserver;
use Database\Factories\DealFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $deal_number
 * @property int $doctor_id
 * @property int $sponsor_id
 * @property int|null $package_id
 * @property string $final_price
 * @property DealStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['deal_number', 'doctor_id', 'sponsor_id', 'package_id', 'final_price', 'status'])]
#[ObservedBy([DealObserver::class])]
class Deal extends Model
{
    /** @use HasFactory<DealFactory> */
    use HasFactory;

    /** @return BelongsTo<User, $this> */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /** @return BelongsTo<Sponsor, $this> */
    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(Sponsor::class);
    }

    /** @return BelongsTo<Package, $this> */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /** @return BelongsToMany<Item, $this, DealItem> */
    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'deal_items')
            ->using(DealItem::class)
            ->withPivot(['is_addon', 'custom_price'])
            ->withTimestamps();
    }

    /** @return HasMany<PaymentTerm, $this> */
    public function paymentTerms(): HasMany
    {
        return $this->hasMany(PaymentTerm::class);
    }

    /** @return HasMany<MaterialDeadline, $this> */
    public function materialDeadlines(): HasMany
    {
        return $this->hasMany(MaterialDeadline::class);
    }

    /** @return HasMany<ActivityLog, $this> */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    /** @return HasMany<DealAsset, $this> */
    public function assets(): HasMany
    {
        return $this->hasMany(DealAsset::class);
    }

    protected function casts(): array
    {
        return [
            'status' => DealStatus::class,
            'final_price' => 'decimal:2',
        ];
    }
}
