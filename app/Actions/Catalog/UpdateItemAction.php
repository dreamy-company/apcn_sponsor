<?php

namespace App\Actions\Catalog;

use App\DTOs\Catalog\ItemData;
use App\Models\Item;
use Illuminate\Support\Facades\DB;

class UpdateItemAction
{
    public function execute(Item $item, ItemData $data): Item
    {
        return DB::transaction(function () use ($item, $data): Item {
            $item->update([
                'name' => $data->name,
                'type' => $data->type,
                'quota' => $data->quota,
                'default_price' => $data->defaultPrice,
                'requires_material' => $data->requiresMaterial,
            ]);

            return $item;
        });
    }
}
