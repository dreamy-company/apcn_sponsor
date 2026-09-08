<?php

namespace App\Models;

use App\Support\ActivityDescriber;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Audit trail entry for a deal. Written automatically by observers.
 *
 * @property int $id
 * @property int $deal_id
 * @property int|null $user_id
 * @property string $action
 * @property array<string, mixed>|null $details
 * @property Carbon $created_at
 */
#[Fillable(['deal_id', 'user_id', 'action', 'details'])]
class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    /**
     * One plain-Indonesian sentence describing this entry, for the UI.
     * The stored slug is untouched — see App\Support\ActivityDescriber.
     */
    public function describe(): string
    {
        return app(ActivityDescriber::class)->describe($this);
    }

    /** @return BelongsTo<Deal, $this> */
    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'details' => 'array',
        ];
    }
}
