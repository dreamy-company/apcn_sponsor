<?php

namespace App\Livewire;

use App\Models\Setting;
use App\Services\DashboardService;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;
use Mary\Traits\Toast;

class Dashboard extends Component
{
    use Toast;

    public string $accessCode = '';

    public function mount(): void
    {
        if (auth()->user()->isJ4u()) {
            // Ensure a public report link always exists for admins to share.
            if (Setting::get('report_public_token') === null) {
                Setting::set('report_public_token', Str::random(40));
            }

            $this->accessCode = Setting::get('public_access_code', '') ?? '';
        }
    }

    public function regenerateReportLink(): void
    {
        abort_unless(auth()->user()->isJ4u(), 403);

        Setting::set('report_public_token', Str::random(40));

        $this->success(__('New report link generated. The old link no longer works.'));
    }

    public function saveAccessCode(): void
    {
        abort_unless(auth()->user()->isJ4u(), 403);

        $this->validate(['accessCode' => ['required', 'string', 'min:4', 'max:100']]);

        Setting::set('public_access_code', $this->accessCode);

        $this->success(__('Access code updated.'));
    }

    public function render(): View
    {
        // App is admin-only; the dashboard always shows global figures.
        $service = app(DashboardService::class);

        $token = Setting::get('report_public_token');

        return view('livewire.dashboard', [
            'summary' => $service->summary(),
            'recentDeals' => $service->recentDeals(),
            'reportUrl' => $token !== null ? route('public.report', $token) : null,
        ]);
    }
}
