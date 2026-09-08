<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

/**
 * Shared behaviour for models carrying a transfer-proof file in the
 * proof_disk / proof_path / proof_original_name / proof_size columns.
 *
 * @property string|null $proof_disk
 * @property string|null $proof_path
 * @property string|null $proof_original_name
 * @property int|null $proof_size
 */
trait HasTransferProof
{
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
        return $this->fileDownloadName($this->proof_original_name ?? 'bukti-transfer', $this->proof_path);
    }

    public function proofHumanSize(): string
    {
        return Number::fileSize($this->proof_size ?? 0, precision: 1);
    }

    /**
     * Original filename with its extension re-appended if missing.
     */
    protected function fileDownloadName(string $name, ?string $path): string
    {
        $ext = pathinfo($path ?? '', PATHINFO_EXTENSION);

        if ($ext !== '' && ! Str::endsWith(Str::lower($name), '.'.Str::lower($ext))) {
            return $name.'.'.$ext;
        }

        return $name;
    }
}
