<?php

namespace App\Models;

use App\Enums\MaterialStatus;
use App\Observers\MaterialDeadlineObserver;
use Database\Factories\MaterialDeadlineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $deal_id
 * @property int $item_id
 * @property string $material_name
 * @property Carbon|null $due_date
 * @property MaterialStatus $status
 * @property Carbon|null $received_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['deal_id', 'item_id', 'material_name', 'due_date', 'status', 'received_at'])]
#[ObservedBy([MaterialDeadlineObserver::class])]
class MaterialDeadline extends Model
{
    /** @use HasFactory<MaterialDeadlineFactory> */
    use HasFactory;

    /** @return BelongsTo<Deal, $this> */
    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    /** @return BelongsTo<Item, $this> */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    protected function casts(): array
    {
        return [
            'status' => MaterialStatus::class,
            'due_date' => 'date',
            'received_at' => 'datetime',
        ];
    }
}
