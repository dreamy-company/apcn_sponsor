<?php

namespace App\Livewire\Catalog;

use App\Models\Item;
use App\Services\QuotaService;
use Illuminate\View\View;
use Livewire\Component;

class CatalogItemShow extends Component
{
    public Item $item;

    public function mount(Item $item): void
    {
        abort_unless(auth()->user()->isJ4u(), 403);

        $this->item = $item;
    }

    public function render(): View
    {
        $deals = $this->item->deals()
            ->with('sponsor')
            ->latest('deals.id')
            ->get();

        return view('livewire.catalog.item-show', [
            'deals' => $deals,
            'taken' => app(QuotaService::class)->itemTakenCount($this->item->id),
        ]);
    }
}
