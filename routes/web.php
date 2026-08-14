<?php

use App\Livewire\Catalog\CatalogItemForm;
use App\Livewire\Catalog\CatalogItemIndex;
use App\Livewire\Catalog\CatalogPackageForm;
use App\Livewire\Catalog\CatalogPackageIndex;
use App\Livewire\Dashboard;
use App\Livewire\DealForm;
use App\Livewire\DealList;
use App\Livewire\DealShow;
use App\Livewire\Doctors\DoctorForm;
use App\Livewire\Doctors\DoctorIndex;
use App\Livewire\Public\DoctorPortal;
use App\Livewire\Users\UserForm;
use App\Livewire\Users\UserIndex;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // Guests land on the login page; authenticated users go straight to the dashboard.
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
})->name('home');

// Public per-doctor portal (no auth) — gated by the global access code.
Route::get('/d/{token}', DoctorPortal::class)->name('public.doctor');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', Dashboard::class)->name('dashboard');

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

    // Doctors management (J4U-only) — non-login initiators with public links
    Route::middleware('role:j4u')->prefix('doctors')->group(function () {
        Route::get('/', DoctorIndex::class)->name('doctors.index');
        Route::get('/create', DoctorForm::class)->name('doctors.create');
        Route::get('/{doctor}/edit', DoctorForm::class)->name('doctors.edit');
    });

    // User management (J4U-only)
    Route::middleware('role:j4u')->prefix('users')->group(function () {
        Route::get('/', UserIndex::class)->name('users.index');
        Route::get('/create', UserForm::class)->name('users.create');
        Route::get('/{user}/edit', UserForm::class)->name('users.edit');
    });
});

require __DIR__.'/settings.php';
