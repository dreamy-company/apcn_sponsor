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
                'default_price_idr' => $data->defaultPriceIdr,
                'default_price_usd' => $data->defaultPriceUsd,
                'quota' => $data->quota,
            ]);

            $package->items()->sync($data->itemPivot());

            return $package;
        });
    }
}
