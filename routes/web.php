<?php

use App\Livewire\Catalog\CatalogItemForm;
use App\Livewire\Catalog\CatalogItemIndex;
use App\Livewire\Catalog\CatalogPackageForm;
use App\Livewire\Catalog\CatalogPackageIndex;
use App\Livewire\DealForm;
use App\Livewire\DealList;
use App\Livewire\DealShow;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::get('/deals', DealList::class)->name('deals.index');

    // J4U-only write routes
    Route::middleware('role:j4u')->group(function () {
        // Defined before /deals/{deal} so 'create' is not captured by the wildcard.
        Route::get('/deals/create', DealForm::class)->name('deals.create');
        Route::get('/deals/{deal}/edit', DealForm::class)->name('deals.edit');
    });

    Route::get('/deals/{deal}', DealShow::class)->name('deals.show');

    // Catalog management (J4U-only)
    Route::middleware('role:j4u')->prefix('catalog')->group(function () {
        Route::get('/items', CatalogItemIndex::class)->name('catalog.items.index');
        Route::get('/items/create', CatalogItemForm::class)->name('catalog.items.create');
        Route::get('/items/{item}/edit', CatalogItemForm::class)->name('catalog.items.edit');

        Route::get('/packages', CatalogPackageIndex::class)->name('catalog.packages.index');
        Route::get('/packages/create', CatalogPackageForm::class)->name('catalog.packages.create');
        Route::get('/packages/{package}/edit', CatalogPackageForm::class)->name('catalog.packages.edit');
    });
});

require __DIR__.'/settings.php';
