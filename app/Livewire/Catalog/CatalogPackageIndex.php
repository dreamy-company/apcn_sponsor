<?php

namespace App\Livewire\Catalog;

use App\Models\Package;
use Illuminate\View\View;
use Livewire\Component;
use Mary\Traits\Toast;

class CatalogPackageIndex extends Component
{
    use Toast;

    public function mount(): void
    {
        abort_unless(auth()->user()->isJ4u(), 403);
    }

    public function delete(int $packageId): void
    {
        $this->authorizeJ4u();

        $package = Package::findOrFail($packageId);

        if ($package->deals()->exists()) {
            $this->error(__('This package is used by one or more deals and cannot be deleted.'));

            return;
        }

        $package->delete();

        $this->success(__('Package deleted.'));
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
