<?php

namespace App\Actions\Catalog;

use App\DTOs\Catalog\PackageData;
use App\Models\Package;
use Illuminate\Support\Facades\DB;

class UpdatePackageAction
{
    public function execute(Package $package, PackageData $data): Package
    {
        return DB::transaction(function () use ($package, $data): Package {
            $package->update([
                'name' => $data->name,
                'default_price' => $data->defaultPrice,
            ]);

            $package->items()->sync($data->itemIds);

            return $package;
        });
    }
}
