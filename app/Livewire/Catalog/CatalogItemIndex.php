<?php

namespace App\Livewire\Catalog;

use App\Enums\DealStatus;
use App\Models\Item;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Mary\Traits\Toast;

class CatalogItemIndex extends Component
{
    use Toast;

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
            $this->error(__('This item is part of one or more deals and cannot be deleted.'));

            return;
        }

        $item->delete();

        $this->success(__('Item deleted.'));
    }

    public function render(): View
    {
        return view('livewire.catalog.item-index', [
            'items' => Item::query()
                ->withCount(['deals as taken_count' => fn ($q) => $q->where('deals.status', DealStatus::Finalized->value)])
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
