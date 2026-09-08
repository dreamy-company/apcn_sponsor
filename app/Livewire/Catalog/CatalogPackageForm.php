<?php

namespace App\Livewire\Catalog;

use App\Actions\Catalog\CreatePackageAction;
use App\Actions\Catalog\UpdatePackageAction;
use App\DTOs\Catalog\PackageData;
use App\Models\Item;
use App\Models\Package;
use App\Support\Money;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\View\View;
use Livewire\Component;

class CatalogPackageForm extends Component
{
    public ?Package $package = null;

    public string $name = '';

    public string $defaultPriceIdr = '';

    public string $defaultPriceUsd = '';

    public ?int $quota = null;

    /** @var list<int> */
    public array $selectedItems = [];

    /**
     * Units of each item this tier includes, keyed by item id. Only the entries
     * for selected items are meaningful; the rest are kept so toggling an item
     * off and on again does not lose the number.
     *
     * @var array<int, int>
     */
    public array $itemQuantities = [];

    public function mount(?Package $package = null): void
    {
        abort_unless(auth()->user()->isJ4u(), 403);

        $this->package = $package;

        if ($package) {
            $package->load('items');

            $this->name = $package->name;
            $this->defaultPriceIdr = Money::plain($package->default_price_idr);
            $this->defaultPriceUsd = Money::plain($package->default_price_usd);
            $this->quota = $package->quota;
            $this->selectedItems = array_values($package->items->pluck('id')->all());
            $this->itemQuantities = $package->items
                ->mapWithKeys(fn (Item $item): array => [
                    $item->id => max(1, (int) $item->getAttribute('pivot')->quantity),
                ])->all();
        }
    }

    public function save(): void
    {
        $validated = $this->validate();

        $data = new PackageData(
            name: $validated['name'],
            defaultPriceIdr: $validated['defaultPriceIdr'] !== '' ? (string) $validated['defaultPriceIdr'] : null,
            defaultPriceUsd: $validated['defaultPriceUsd'] !== '' && $validated['defaultPriceUsd'] !== null ? (string) $validated['defaultPriceUsd'] : null,
            quota: $validated['quota'] !== null && $validated['quota'] !== '' ? (int) $validated['quota'] : null,
            items: $this->selectedItemQuantities(),
        );

        $this->package
            ? app(UpdatePackageAction::class)->execute($this->package, $data)
            : app(CreatePackageAction::class)->execute($data);

        session()->flash('status', $this->package ? 'Package updated.' : 'Package created.');

        $this->redirect(route('catalog.packages.index'), navigate: true);
    }

    /**
     * Selected item ids mapped to their unit count (default 1).
     *
     * @return array<int, int>
     */
    protected function selectedItemQuantities(): array
    {
        $items = [];

        foreach ($this->selectedItems as $id) {
            $itemId = (int) $id;
            $items[$itemId] = max(1, (int) ($this->itemQuantities[$itemId] ?? 1));
        }

        return $items;
    }

    public function render(): View
    {
        return view('livewire.catalog.package-form', [
            'items' => Item::orderBy('name')->get(),
        ]);
    }

    /**
     * @return array<string, list<string|Exists>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'defaultPriceIdr' => ['required', 'numeric', 'min:0'],
            'defaultPriceUsd' => ['nullable', 'numeric', 'min:0'],
            'quota' => ['nullable', 'integer', 'min:0'],
            'selectedItems' => ['array'],
            'selectedItems.*' => ['integer', Rule::exists('items', 'id')],
            'itemQuantities' => ['array'],
            'itemQuantities.*' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
