<?php

namespace App\Livewire\Public;

use App\Models\Setting;
use App\Models\User;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.public')]
class DoctorPortal extends Component
{
    public User $doctor;

    public bool $unlocked = false;

    public string $code = '';

    public function mount(string $token): void
    {
        $this->doctor = User::doctors()->where('public_token', $token)->firstOrFail();

        $this->unlocked = (bool) session('public_portal_unlocked', false);
    }

    public function unlock(): void
    {
        $this->validate(['code' => ['required', 'string']]);

        $expected = (string) Setting::get('public_access_code', '');

        if ($expected !== '' && hash_equals($expected, $this->code)) {
            session(['public_portal_unlocked' => true]);
            $this->unlocked = true;
            $this->reset('code');

            return;
        }

        $this->addError('code', __('Incorrect access code.'));
    }

    public function render(): View
    {
        $deals = $this->unlocked
            ? $this->doctor->deals()
                ->with(['sponsor', 'package', 'items', 'paymentTerms', 'materialDeadlines'])
                ->latest()
                ->get()
            : collect();

        return view('livewire.public.doctor-portal', ['deals' => $deals]);
    }
}
