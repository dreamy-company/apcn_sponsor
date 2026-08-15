<?php

namespace App\Livewire\Catalog;

use App\Models\Package;
use App\Services\QuotaService;
use Illuminate\View\View;
use Livewire\Component;

class CatalogPackageShow extends Component
{
    public Package $package;

    public function mount(Package $package): void
    {
        abort_unless(auth()->user()->isJ4u(), 403);

        $this->package = $package->load('items');
    }

    public function render(): View
    {
        $deals = $this->package->deals()
            ->with('sponsor')
            ->latest('id')
            ->get();

        return view('livewire.catalog.package-show', [
            'deals' => $deals,
            'taken' => app(QuotaService::class)->packageTakenCount($this->package->id),
        ]);
    }
}
