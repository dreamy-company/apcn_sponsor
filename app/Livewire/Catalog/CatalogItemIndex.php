<?php

namespace App\Livewire\Catalog;

use App\Models\Item;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

class CatalogItemIndex extends Component
{
    #[Url]
    public string $search = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->isJ4u(), 403);
    }

    public function delete(int $itemId): void
    {
        $this->authorizeJ4u();

        $item = Item::findOrFail($itemId);

        if ($item->deals()->exists()) {
            $this->dispatch('toast-show', slots: ['text' => 'This item is part of one or more deals and cannot be deleted.'], dataset: ['variant' => 'danger']);

            return;
        }

        $item->delete();

        $this->dispatch('toast-show', slots: ['text' => 'Item deleted.'], dataset: ['variant' => 'success']);
    }

    public function render(): View
    {
        return view('livewire.catalog.item-index', [
            'items' => Item::query()
                ->when($this->search !== '', fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
                ->orderBy('name')
                ->get(),
        ]);
    }

    protected function authorizeJ4u(): void
    {
        abort_unless(auth()->user()->isJ4u(), 403);
    }
}
