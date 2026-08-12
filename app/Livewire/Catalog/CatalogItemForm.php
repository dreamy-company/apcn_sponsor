<?php

namespace App\Livewire\Catalog;

use App\Actions\Catalog\CreateItemAction;
use App\Actions\Catalog\UpdateItemAction;
use App\DTOs\Catalog\ItemData;
use App\Models\Item;
use Illuminate\View\View;
use Livewire\Component;

class CatalogItemForm extends Component
{
    public ?Item $item = null;

    public string $name = '';

    public string $type = '';

    public bool $requiresMaterial = false;

    public function mount(?Item $item = null): void
    {
        abort_unless(auth()->user()->isJ4u(), 403);

        $this->item = $item;

        if ($item) {
            $this->name = $item->name;
            $this->type = $item->type ?? '';
            $this->requiresMaterial = (bool) $item->requires_material;
        }
    }

    public function save(): void
    {
        $validated = $this->validate();

        $data = new ItemData(
            name: $validated['name'],
            type: $validated['type'] !== '' && $validated['type'] !== null ? $validated['type'] : null,
            requiresMaterial: $validated['requiresMaterial'],
        );

        $this->item
            ? app(UpdateItemAction::class)->execute($this->item, $data)
            : app(CreateItemAction::class)->execute($data);

        session()->flash('status', $this->item ? 'Item updated.' : 'Item created.');

        $this->redirect(route('catalog.items.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.catalog.item-form');
    }

    /**
     * @return array<string, list<string>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:100'],
            'requiresMaterial' => ['boolean'],
        ];
    }
}
