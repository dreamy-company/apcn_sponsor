<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Observers\PaymentTermObserver;
use Database\Factories\PaymentTermFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $deal_id
 * @property string $description
 * @property Carbon $due_date
 * @property string $amount
 * @property PaymentStatus $status
 * @property string|null $proof_disk
 * @property string|null $proof_path
 * @property string|null $proof_original_name
 * @property int|null $proof_size
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['deal_id', 'description', 'due_date', 'amount', 'status', 'proof_disk', 'proof_path', 'proof_original_name', 'proof_size'])]
#[ObservedBy([PaymentTermObserver::class])]
class PaymentTerm extends Model
{
    /** @use HasFactory<PaymentTermFactory> */
    use HasFactory;

    /** @return BelongsTo<Deal, $this> */
    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function hasProof(): bool
    {
        return $this->proof_path !== null;
    }

    /**
     * Public URL to the transfer proof (null when none).
     */
    public function proofUrl(): ?string
    {
        return $this->proof_path !== null
            ? Storage::disk($this->proof_disk ?? 'public')->url($this->proof_path)
            : null;
    }

    /**
     * Filename used when downloading the proof — original name with its
     * extension re-appended if missing.
     */
    public function proofDownloadName(): string
    {
        $name = $this->proof_original_name ?? 'bukti-transfer';
        $ext = pathinfo($this->proof_path ?? '', PATHINFO_EXTENSION);

        if ($ext !== '' && ! Str::endsWith(Str::lower($name), '.'.Str::lower($ext))) {
            return $name.'.'.$ext;
        }

        return $name;
    }

    public function proofHumanSize(): string
    {
        return Number::fileSize($this->proof_size ?? 0, precision: 1);
    }

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'due_date' => 'date',
            'amount' => 'decimal:2',
            'proof_size' => 'integer',
        ];
    }
}
