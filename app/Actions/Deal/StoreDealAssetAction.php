<?php

namespace App\Actions\Deal;

use App\Models\Deal;
use App\Models\DealAsset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class StoreDealAssetAction
{
    /**
     * Store an uploaded file on the public disk and record it against the deal.
     */
    public function execute(Deal $deal, UploadedFile $file, ?int $uploadedBy = null, ?string $name = null): DealAsset
    {
        return DB::transaction(function () use ($deal, $file, $uploadedBy, $name): DealAsset {
            $path = $file->store("deal-assets/{$deal->id}", 'public');

            return $deal->assets()->create([
                'disk' => 'public',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'name' => ($name !== null && trim($name) !== '') ? trim($name) : null,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'uploaded_by_id' => $uploadedBy,
            ]);
        });
    }
}
