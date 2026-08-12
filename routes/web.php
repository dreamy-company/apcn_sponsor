<?php

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
});

require __DIR__.'/settings.php';
