<?php

namespace App\Livewire;

use App\Enums\DealStatus;
use App\Models\Deal;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

class DealList extends Component
{
    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->isJ4u() || auth()->user()->isDoctor(), 403);
    }

    public function render(): View
    {
        $query = Deal::query()
            ->with(['sponsor', 'doctor', 'package'])
            ->when(! auth()->user()->isJ4u(), fn ($q) => $q->where('doctor_id', auth()->id()))
            ->when($this->search !== '', fn ($q) => $q->whereHas('sponsor', fn ($s) => $s->where('company_name', 'like', '%'.$this->search.'%')))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->latest();

        return view('livewire.deal-list', [
            'deals' => $query->get(),
            'statuses' => DealStatus::cases(),
        ]);
    }
}
