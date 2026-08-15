<?php

namespace App\Actions\Deal;

use App\Models\DealAsset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteDealAssetAction
{
    /**
     * Remove the stored file and its record.
     */
    public function execute(DealAsset $asset): void
    {
        DB::transaction(function () use ($asset): void {
            Storage::disk($asset->disk)->delete($asset->path);

            $asset->delete();
        });
    }
}
