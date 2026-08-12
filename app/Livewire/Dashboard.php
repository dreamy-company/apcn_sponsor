<?php

namespace App\Livewire;

use App\Services\DashboardService;
use Illuminate\View\View;
use Livewire\Component;

class Dashboard extends Component
{
    public function render(): View
    {
        $user = auth()->user();

        $scope = $user->isJ4u() ? null : $user->id;

        $service = app(DashboardService::class);

        return view('livewire.dashboard', [
            'summary' => $service->summary($scope),
            'recentDeals' => $service->recentDeals($scope),
        ]);
    }
}
