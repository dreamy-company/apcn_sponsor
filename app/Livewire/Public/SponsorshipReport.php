<?php

namespace App\Livewire\Public;

use App\Models\Setting;
use App\Services\SponsorshipReportService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.public')]
class SponsorshipReport extends Component
{
    public bool $unlocked = false;

    public string $code = '';

    public string $token = '';

    public function mount(string $token): void
    {
        $expected = Setting::get('report_public_token');

        abort_unless($expected !== null && $expected !== '' && hash_equals($expected, $token), 404);

        $this->token = $token;
        $this->unlocked = (bool) session('public_report_unlocked', false);
    }

    public function unlock(): void
    {
        $this->validate(['code' => ['required', 'string']]);

        $expected = (string) Setting::get('public_access_code', '');

        if ($expected !== '' && hash_equals($expected, $this->code)) {
            session(['public_report_unlocked' => true]);
            $this->unlocked = true;
            $this->reset('code');

            return;
        }

        $this->addError('code', __('Incorrect access code.'));
    }

    public function render(): View
    {
        return view('livewire.public.sponsorship-report', [
            'report' => $this->unlocked ? app(SponsorshipReportService::class)->report() : null,
        ]);
    }
}
