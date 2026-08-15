<?php

namespace App\Livewire\Public;

use App\Models\Deal;
use App\Models\Package;
use App\Models\Setting;
use App\Models\Sponsor;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.public')]
class SponsorReport extends Component
{
    public Sponsor $sponsor;

    public string $token = '';

    public bool $unlocked = false;

    public string $code = '';

    public function mount(string $token, Sponsor $sponsor): void
    {
        $expected = Setting::get('report_public_token');

        abort_unless($expected !== null && $expected !== '' && hash_equals($expected, $token), 404);

        $this->token = $token;
        $this->sponsor = $sponsor;
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
        $deals = $this->unlocked
            ? $this->sponsor->deals()
                ->with(['sponsor', 'package', 'items', 'paymentTerms', 'materialDeadlines'])
                ->latest()
                ->get()
            : collect();

        return view('livewire.public.sponsor-report', [
            'deals' => $deals,
            'totalValue' => $deals->sum(fn ($deal): float => (float) $deal->final_price),
            'topPackage' => $deals->map(fn (Deal $deal): ?Package => $deal->package)->filter()
                ->sortByDesc(fn (Package $package): float => (float) $package->default_price)->first(),
        ]);
    }
}
