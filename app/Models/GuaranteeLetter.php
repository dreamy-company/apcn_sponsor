<?php

namespace App\Models;

use App\Enums\GuaranteeLetterStatus;
use App\Models\Concerns\HasTransferProof;
use Database\Factories\GuaranteeLetterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;

/**
 * A guarantee letter (surat jaminan): one way of settling a payment term.
 * Instead of transferring on the due date, the sponsor hands over a letter
 * committing to the term's amount, a payment date is agreed, and on that date
 * the transfer proof settles it.
 *
 * It carries no amount of its own — it guarantees the term's amount (BR-09).
 *
 * @property int $id
 * @property int $payment_term_id
 * @property GuaranteeLetterStatus $status
 * @property Carbon|null $payment_due_date
 * @property string|null $doc_disk
 * @property string|null $doc_path
 * @property string|null $doc_original_name
 * @property int|null $doc_size
 * @property string|null $proof_disk
 * @property string|null $proof_path
 * @property string|null $proof_original_name
 * @property int|null $proof_size
 * @property Carbon|null $verified_at
 * @property int|null $verified_by_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'payment_term_id', 'status', 'payment_due_date',
    'doc_disk', 'doc_path', 'doc_original_name', 'doc_size',
    'proof_disk', 'proof_path', 'proof_original_name', 'proof_size',
    'verified_at', 'verified_by_id',
])]
class GuaranteeLetter extends Model
{
    /** @use HasFactory<GuaranteeLetterFactory> */
    use HasFactory, HasTransferProof;

    /** @return BelongsTo<PaymentTerm, $this> */
    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class);
    }

    /** @return BelongsTo<User, $this> */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_id');
    }

    public function hasDocument(): bool
    {
        return $this->doc_path !== null;
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    /**
     * True once the scheduled payment date has arrived — the transfer proof
     * is expected from this date onward.
     */
    public function isDueForPayment(): bool
    {
        return $this->payment_due_date !== null
            && ! $this->payment_due_date->isFuture();
    }

    public function documentUrl(): ?string
    {
        return $this->doc_path !== null
            ? Storage::disk($this->doc_disk ?? 'public')->url($this->doc_path)
            : null;
    }

    public function documentDownloadName(): string
    {
        return $this->fileDownloadName($this->doc_original_name ?? 'surat-jaminan', $this->doc_path);
    }

    public function documentHumanSize(): string
    {
        return Number::fileSize($this->doc_size ?? 0, precision: 1);
    }

    protected function casts(): array
    {
        return [
            'status' => GuaranteeLetterStatus::class,
            'payment_due_date' => 'date',
            'doc_size' => 'integer',
            'proof_size' => 'integer',
            'verified_at' => 'datetime',
        ];
    }
}
