<?php

namespace App\Livewire;

use App\Services\DashboardService;
use Illuminate\View\View;
use Livewire\Component;

class Dashboard extends Component
{
    public function render(): View
    {
        // App is admin-only; the dashboard always shows global figures.
        $service = app(DashboardService::class);

        return view('livewire.dashboard', [
            'summary' => $service->summary(),
            'recentDeals' => $service->recentDeals(),
        ]);
    }
}
