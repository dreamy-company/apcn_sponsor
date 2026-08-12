<?php

namespace App\Actions\Catalog;

use App\DTOs\Catalog\PackageData;
use App\Models\Package;
use Illuminate\Support\Facades\DB;

class CreatePackageAction
{
    public function execute(PackageData $data): Package
    {
        return DB::transaction(function () use ($data): Package {
            $package = Package::create([
                'name' => $data->name,
                'default_price' => $data->defaultPrice,
            ]);

            $package->items()->sync($data->itemIds);

            return $package;
        });
    }
}
