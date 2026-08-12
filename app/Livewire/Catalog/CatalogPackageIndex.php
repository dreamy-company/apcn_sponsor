<?php

namespace App\Livewire\Catalog;

use App\Models\Package;
use Illuminate\View\View;
use Livewire\Component;

class CatalogPackageIndex extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->isJ4u(), 403);
    }

    public function delete(int $packageId): void
    {
        $this->authorizeJ4u();

        $package = Package::findOrFail($packageId);

        if ($package->deals()->exists()) {
            $this->dispatch('toast-show', slots: ['text' => 'This package is used by one or more deals and cannot be deleted.'], dataset: ['variant' => 'danger']);

            return;
        }

        $package->delete();

        $this->dispatch('toast-show', slots: ['text' => 'Package deleted.'], dataset: ['variant' => 'success']);
    }

    public function render(): View
    {
        return view('livewire.catalog.package-index', [
            'packages' => Package::withCount('items')->orderBy('default_price', 'desc')->get(),
        ]);
    }

    protected function authorizeJ4u(): void
    {
        abort_unless(auth()->user()->isJ4u(), 403);
    }
}
