<?php

namespace App\Services;

use App\Models\Deal;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class DealAssetArchiver
{
    /**
     * Build a temporary zip of all of a deal's assets, named by their display
     * names. Returns the temp file path, or null when the deal has no assets.
     */
    public function zip(Deal $deal): ?string
    {
        $assets = $deal->assets()->get();

        if ($assets->isEmpty()) {
            return null;
        }

        $zipPath = tempnam(sys_get_temp_dir(), 'deal-assets-').'.zip';

        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        /** @var array<string, int> $used */
        $used = [];

        foreach ($assets as $asset) {
            $disk = Storage::disk($asset->disk);

            if (! $disk->exists($asset->path)) {
                continue;
            }

            $zip->addFile($disk->path($asset->path), $this->uniqueName($asset->downloadName(), $used));
        }

        $zip->close();

        return $zipPath;
    }

    /**
     * Ensure entry names are unique inside the archive by suffixing " (2)", etc.
     *
     * @param  array<string, int>  $used
     */
    private function uniqueName(string $name, array &$used): string
    {
        $key = strtolower($name);

        if (! isset($used[$key])) {
            $used[$key] = 1;

            return $name;
        }

        $used[$key]++;

        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $base = $ext !== '' ? substr($name, 0, -\strlen($ext) - 1) : $name;

        return $ext !== ''
            ? $base.' ('.$used[$key].').'.$ext
            : $base.' ('.$used[$key].')';
    }
}
