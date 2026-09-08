<?php

namespace App\Support;

use App\Enums\Currency;
use App\Models\ActivityLog;
use Illuminate\Support\Carbon;

/**
 * Turns an audit entry into one plain-Indonesian sentence for the UI.
 *
 * The stored `action` slugs and `details` payloads are never changed — the
 * audit trail stays machine-readable (BR-06); this is presentation only.
 *
 * Three `details` shapes have to be tolerated:
 *   - a flat attribute snapshot (the three `*.created` actions)
 *   - a `field => ['old' => ..., 'new' => ...]` map (everything else)
 *   - null
 */
class ActivityDescriber
{
    /**
     * Human labels for the fields that show up in change maps.
     */
    private const FIELDS = [
        'deal_number' => 'nomor deal',
        'doctor_id' => 'dokter penginisiasi',
        'sponsor_id' => 'sponsor',
        'package_id' => 'paket',
        'currency' => 'mata uang',
        'subtotal' => 'subtotal',
        'final_price' => 'harga akhir',
        'status' => 'status',
        'description' => 'keterangan',
        'due_date' => 'tanggal jatuh tempo',
        'amount' => 'nominal',
        'notes' => 'catatan',
        'material_name' => 'nama materi',
        'received_at' => 'tanggal diterima',
        'verified_at' => 'tanggal verifikasi',
        'payment_due_date' => 'tanggal pembayaran',
    ];

    /**
     * Human labels for the status values that appear in change maps.
     */
    private const VALUES = [
        'draft' => 'draft',
        'finalized' => 'final',
        'pending' => 'menunggu',
        'paid' => 'lunas',
        'received' => 'diterima',
    ];

    public function describe(ActivityLog $log): string
    {
        $details = is_array($log->details) ? $log->details : [];

        return match ($log->action) {
            'deal.created' => 'membuat deal ini',
            'deal.updated' => $this->dealUpdated($details),

            'payment_term.created' => $this->termCreated($details),
            'payment_term.updated' => $this->termUpdated($details),
            'payment_term.proof_uploaded' => 'mengunggah bukti transfer'.$this->file($details),
            'payment_term.proof_removed' => 'menghapus bukti transfer',
            'payment_term.verified' => 'memverifikasi pembayaran'.$this->of($details, 'description'),
            'payment_term.unverified' => 'membatalkan verifikasi pembayaran'.$this->of($details, 'description'),

            'material_deadline.created' => 'menambahkan materi ke checklist'.$this->of($details, 'material_name'),
            'material_deadline.updated' => $this->materialUpdated($details),

            'guarantee_letter.created' => 'menambahkan surat jaminan'.$this->of($details, 'term').$this->file($details, 'document'),
            'guarantee_letter.updated' => 'mengganti surat jaminan'.$this->of($details, 'term').$this->file($details, 'document'),
            'guarantee_letter.scheduled' => $this->letterScheduled($details),
            'guarantee_letter.proof_uploaded' => 'mengunggah bukti pembayaran surat jaminan'.$this->file($details),
            'guarantee_letter.verified' => 'memverifikasi pembayaran surat jaminan',
            'guarantee_letter.unverified' => 'membatalkan verifikasi surat jaminan',

            // An action we have no wording for yet still has to read as a sentence.
            default => 'melakukan perubahan ('.str_replace(['.', '_'], [' ', ' '], $log->action).')',
        };
    }

    /**
     * @param  array<string, mixed>  $details
     */
    private function dealUpdated(array $details): string
    {
        $status = $this->statusChange($details);

        if ($status !== null) {
            return $status === 'final'
                ? 'memfinalkan deal ini'
                : 'mengubah status deal menjadi '.$status;
        }

        $fields = $this->changedFields($details);

        return $fields === []
            ? 'memperbarui deal ini'
            : 'memperbarui '.$this->join($fields).' pada deal ini';
    }

    /**
     * @param  array<string, mixed>  $details
     */
    private function termUpdated(array $details): string
    {
        $status = $this->statusChange($details);

        if ($status === 'lunas') {
            return 'menandai termin pembayaran sebagai lunas';
        }

        if ($status !== null) {
            return 'mengubah status termin pembayaran menjadi '.$status;
        }

        $fields = $this->changedFields($details);

        return $fields === []
            ? 'memperbarui termin pembayaran'
            : 'memperbarui '.$this->join($fields).' pada termin pembayaran';
    }

    /**
     * @param  array<string, mixed>  $details
     */
    private function materialUpdated(array $details): string
    {
        $status = $this->statusChange($details);

        if ($status === 'diterima') {
            return 'menandai materi sudah diterima';
        }

        $fields = $this->changedFields($details);

        return $fields === []
            ? 'memperbarui materi'
            : 'memperbarui '.$this->join($fields).' pada materi';
    }

    /**
     * `payment_term.created` stores a flat attribute snapshot.
     *
     * @param  array<string, mixed>  $details
     */
    private function termCreated(array $details): string
    {
        $label = $this->plain($details, 'description');
        $amount = $this->plain($details, 'amount');

        $sentence = 'menambahkan termin pembayaran';

        if ($label !== null) {
            $sentence .= ' "'.$label.'"';
        }

        if ($amount !== null) {
            $sentence .= ' sebesar '.Currency::IDR->format($amount);
        }

        return $sentence;
    }

    /**
     * @param  array<string, mixed>  $details
     */
    private function letterScheduled(array $details): string
    {
        $date = $this->newValue($details, 'payment_due_date');

        return $date === null
            ? 'menjadwalkan pembayaran surat jaminan'
            : 'menjadwalkan pembayaran surat jaminan pada '.$this->date($date);
    }

    /**
     * Human status value from a `{old,new}` change map, if there was one.
     *
     * @param  array<string, mixed>  $details
     */
    private function statusChange(array $details): ?string
    {
        $new = $this->newValue($details, 'status');

        return $new === null ? null : (self::VALUES[$new] ?? $new);
    }

    /**
     * Field labels touched by a `{old,new}` change map.
     *
     * @param  array<string, mixed>  $details
     * @return list<string>
     */
    private function changedFields(array $details): array
    {
        $fields = [];

        foreach ($details as $field => $change) {
            // Only pairs describe a change; a flat snapshot says nothing useful.
            if (! is_array($change) || ! array_key_exists('new', $change)) {
                continue;
            }

            if ($field === 'updated_at') {
                continue;
            }

            $fields[] = self::FIELDS[$field] ?? str_replace('_', ' ', (string) $field);
        }

        return $fields;
    }

    /**
     * The `new` side of a change pair, as a string.
     *
     * @param  array<string, mixed>  $details
     */
    private function newValue(array $details, string $field): ?string
    {
        $change = $details[$field] ?? null;

        if (! is_array($change) || ! isset($change['new']) || $change['new'] === '') {
            return null;
        }

        return is_scalar($change['new']) ? (string) $change['new'] : null;
    }

    /**
     * A value from a flat attribute snapshot.
     *
     * @param  array<string, mixed>  $details
     */
    private function plain(array $details, string $field): ?string
    {
        $value = $details[$field] ?? null;

        return is_scalar($value) && $value !== '' ? (string) $value : null;
    }

    /**
     * ` (nama-berkas.pdf)` when the payload names a file.
     *
     * @param  array<string, mixed>  $details
     */
    private function file(array $details, string $key = 'file'): string
    {
        $name = $this->newValue($details, $key);

        return $name === null ? '' : ' ('.$name.')';
    }

    /**
     * ` "Termin 1"` when the payload names the thing acted on.
     *
     * @param  array<string, mixed>  $details
     */
    private function of(array $details, string $key): string
    {
        $name = $this->newValue($details, $key) ?? $this->plain($details, $key);

        return $name === null ? '' : ' "'.$name.'"';
    }

    /**
     * @param  list<string>  $parts
     */
    private function join(array $parts): string
    {
        $parts = array_values(array_unique($parts));

        if (count($parts) === 1) {
            return $parts[0];
        }

        $last = array_pop($parts);

        return implode(', ', $parts).' dan '.$last;
    }

    private function date(string $value): string
    {
        try {
            return Carbon::parse($value)->format('d M Y');
        } catch (\Throwable) {
            return $value;
        }
    }
}
