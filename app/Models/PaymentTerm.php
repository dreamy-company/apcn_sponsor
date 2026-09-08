<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Models\Concerns\HasTransferProof;
use App\Observers\PaymentTermObserver;
use Database\Factories\PaymentTermFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $deal_id
 * @property string $description
 * @property Carbon $due_date
 * @property numeric-string $amount
 * @property string|null $notes
 * @property PaymentStatus $status
 * @property string|null $proof_disk
 * @property string|null $proof_path
 * @property string|null $proof_original_name
 * @property int|null $proof_size
 * @property Carbon|null $verified_at
 * @property int|null $verified_by_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['deal_id', 'description', 'due_date', 'amount', 'notes', 'status', 'proof_disk', 'proof_path', 'proof_original_name', 'proof_size', 'verified_at', 'verified_by_id'])]
#[ObservedBy([PaymentTermObserver::class])]
class PaymentTerm extends Model
{
    /** @use HasFactory<PaymentTermFactory> */
    use HasFactory, HasTransferProof;

    /** @return BelongsTo<Deal, $this> */
    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    /** @return BelongsTo<User, $this> */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_id');
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    /** @return HasOne<GuaranteeLetter, $this> */
    public function guaranteeLetter(): HasOne
    {
        return $this->hasOne(GuaranteeLetter::class);
    }

    /**
     * True when this term is settled by a guarantee letter rather than a
     * direct transfer.
     */
    public function usesGuaranteeLetter(): bool
    {
        return $this->guaranteeLetter !== null;
    }

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'due_date' => 'date',
            'amount' => 'decimal:2',
            'proof_size' => 'integer',
            'verified_at' => 'datetime',
        ];
    }
}
