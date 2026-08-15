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
            'quota' => $data->quota,
            'requires_material' => $data->requiresMaterial,
        ]));
    }
}
