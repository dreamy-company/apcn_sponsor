<?php

namespace App\Models;

use Database\Factories\DealAssetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

/**
 * A file the admin attaches to a deal for the sponsor to use
 * (contract, artwork, video, …).
 *
 * @property int $id
 * @property int $deal_id
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property string|null $name
 * @property string|null $mime_type
 * @property int $size
 * @property int|null $uploaded_by_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['deal_id', 'disk', 'path', 'original_name', 'name', 'mime_type', 'size', 'uploaded_by_id'])]
class DealAsset extends Model
{
    /** @use HasFactory<DealAssetFactory> */
    use HasFactory;

    /** @return BelongsTo<Deal, $this> */
    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    /** @return BelongsTo<User, $this> */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }

    /**
     * Public URL to download/preview the file.
     */
    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    /**
     * Friendly label shown in the UI (falls back to the original filename).
     */
    public function displayName(): string
    {
        return ($this->name !== null && $this->name !== '') ? $this->name : $this->original_name;
    }

    /**
     * Filename used when downloading — the display name with the original
     * extension appended when it is missing.
     */
    public function downloadName(): string
    {
        $display = $this->displayName();
        $ext = pathinfo($this->original_name, PATHINFO_EXTENSION);

        if ($ext !== '' && ! Str::endsWith(Str::lower($display), '.'.Str::lower($ext))) {
            return $display.'.'.$ext;
        }

        return $display;
    }

    /**
     * Human-readable size (e.g. "34.2 MB").
     */
    public function humanSize(): string
    {
        return Number::fileSize($this->size, precision: 1);
    }

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }
}
