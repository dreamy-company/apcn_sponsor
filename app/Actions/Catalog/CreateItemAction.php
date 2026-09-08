<?php

namespace App\Actions\Catalog;

use App\DTOs\Catalog\ItemData;
use App\Models\Item;
use Illuminate\Support\Facades\DB;

class CreateItemAction
{
    public function execute(ItemData $data): Item
    {
        return DB::transaction(fn (): Item => Item::create([
            'name' => $data->name,
            'type' => $data->type,
            'inclusion' => $data->inclusion,
            'quota' => $data->quota,
            'default_price_idr' => $data->defaultPriceIdr,
            'default_price_usd' => $data->defaultPriceUsd,
            'requires_material' => $data->requiresMaterial,
        ]));
    }
}
