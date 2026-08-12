<?php

namespace App\Livewire\Catalog;

use App\Actions\Catalog\CreatePackageAction;
use App\Actions\Catalog\UpdatePackageAction;
use App\DTOs\Catalog\PackageData;
use App\Models\Item;
use App\Models\Package;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\View\View;
use Livewire\Component;

class CatalogPackageForm extends Component
{
    public ?Package $package = null;

    public string $name = '';

    public string $defaultPrice = '';

    /** @var list<int> */
    public array $selectedItems = [];

    public function mount(?Package $package = null): void
    {
        abort_unless(auth()->user()->isJ4u(), 403);

        $this->package = $package;

        if ($package) {
            $package->load('items');

            $this->name = $package->name;
            $this->defaultPrice = $package->default_price;
            $this->selectedItems = array_values($package->items->pluck('id')->all());
        }
    }

    public function save(): void
    {
        $validated = $this->validate();

        $data = new PackageData(
            name: $validated['name'],
            defaultPrice: $validated['defaultPrice'],
            itemIds: array_values(collect($this->selectedItems)->map(fn (mixed $id): int => (int) $id)->all()),
        );

        $this->package
            ? app(UpdatePackageAction::class)->execute($this->package, $data)
            : app(CreatePackageAction::class)->execute($data);

        session()->flash('status', $this->package ? 'Package updated.' : 'Package created.');

        $this->redirect(route('catalog.packages.index'), navigate: true);
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
            'defaultPrice' => ['required', 'numeric', 'min:0'],
            'selectedItems' => ['array'],
            'selectedItems.*' => ['integer', Rule::exists('items', 'id')],
        ];
    }
}
