<?php

namespace App\Livewire\Catalog;

use App\Actions\Catalog\CreateItemAction;
use App\Actions\Catalog\UpdateItemAction;
use App\DTOs\Catalog\ItemData;
use App\Models\Item;
use App\Support\Money;
use Illuminate\View\View;
use Livewire\Component;

class CatalogItemForm extends Component
{
    public ?Item $item = null;

    public string $name = '';

    public string $type = '';

    public string $inclusion = '';

    public ?int $quota = null;

    public string $defaultPriceIdr = '';

    public string $defaultPriceUsd = '';

    public bool $requiresMaterial = false;

    public function mount(?Item $item = null): void
    {
        abort_unless(auth()->user()->isJ4u(), 403);

        $this->item = $item;

        if ($item) {
            $this->name = $item->name;
            $this->type = $item->type ?? '';
            $this->inclusion = $item->inclusion ?? '';
            $this->quota = $item->quota;
            $this->defaultPriceIdr = Money::plain($item->default_price_idr);
            $this->defaultPriceUsd = Money::plain($item->default_price_usd);
            $this->requiresMaterial = (bool) $item->requires_material;
        }
    }

    public function save(): void
    {
        $validated = $this->validate();

        $data = new ItemData(
            name: $validated['name'],
            type: $validated['type'] !== '' && $validated['type'] !== null ? $validated['type'] : null,
            inclusion: $validated['inclusion'] !== '' ? $validated['inclusion'] : null,
            quota: $validated['quota'] !== null && $validated['quota'] !== '' ? (int) $validated['quota'] : null,
            defaultPriceIdr: $this->nullablePrice($validated['defaultPriceIdr']),
            defaultPriceUsd: $this->nullablePrice($validated['defaultPriceUsd']),
            requiresMaterial: $validated['requiresMaterial'],
        );

        $this->item
            ? app(UpdateItemAction::class)->execute($this->item, $data)
            : app(CreateItemAction::class)->execute($data);

        session()->flash('status', $this->item ? 'Item updated.' : 'Item created.');

        $this->redirect(route('catalog.items.index'), navigate: true);
    }

    /**
     * A blank price means "quote on request", stored as NULL.
     */
    private function nullablePrice(mixed $value): ?string
    {
        return $value !== '' && $value !== null ? (string) $value : null;
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
            'inclusion' => ['nullable', 'string', 'max:2000'],
            'quota' => ['nullable', 'integer', 'min:0'],
            'defaultPriceIdr' => ['nullable', 'numeric', 'min:0'],
            'defaultPriceUsd' => ['nullable', 'numeric', 'min:0'],
            'requiresMaterial' => ['boolean'],
        ];
    }
}
