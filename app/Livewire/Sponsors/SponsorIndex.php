<?php

namespace App\Livewire\Sponsors;

use App\Models\Sponsor;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Mary\Traits\Toast;

class SponsorIndex extends Component
{
    use Toast;

    #[Url]
    public string $search = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->isJ4u(), 403);
    }

    public function delete(int $sponsorId): void
    {
        $this->authorizeJ4u();

        $sponsor = Sponsor::findOrFail($sponsorId);

        if ($sponsor->deals()->exists()) {
            $this->error(__('This sponsor has deals and cannot be deleted.'));

            return;
        }

        $sponsor->delete();

        $this->success(__('Sponsor deleted.'));
    }

    public function render(): View
    {
        return view('livewire.sponsors.sponsor-index', [
            'sponsors' => Sponsor::query()
                ->with('deals.package')
                ->withCount('deals')
                ->withSum('deals', 'final_price')
                ->when($this->search !== '', fn ($q) => $q->where('company_name', 'like', '%'.$this->search.'%'))
                ->orderBy('company_name')
                ->get(),
        ]);
    }

    protected function authorizeJ4u(): void
    {
        abort_unless(auth()->user()->isJ4u(), 403);
    }
}
