<?php

namespace App\Livewire;

use App\Actions\Deal\FinalizeDealAction;
use App\Actions\Deal\MarkMaterialReceivedAction;
use App\Actions\Deal\MarkPaymentTermPaidAction;
use App\Enums\MaterialStatus;
use App\Enums\PaymentStatus;
use App\Models\Deal;
use App\Models\MaterialDeadline;
use App\Models\PaymentTerm;
use Illuminate\View\View;
use Livewire\Component;

class DealShow extends Component
{
    public Deal $deal;

    public function mount(Deal $deal): void
    {
        $user = auth()->user();

        abort_unless($user->isJ4u() || $deal->doctor_id === $user->id, 403);

        $this->deal = $deal->load([
            'sponsor',
            'doctor',
            'package',
            'items',
            'paymentTerms',
            'materialDeadlines.item',
            'activityLogs.user',
        ]);
    }

    public function finalize(): void
    {
        $this->authorizeJ4u();

        app(FinalizeDealAction::class)->execute($this->deal);

        $this->deal->refresh();

        $this->dispatch('toast-show', slots: ['text' => 'Deal finalized — material checklist generated.'], dataset: ['variant' => 'success']);
    }

    public function markPaymentPaid(int $paymentTermId): void
    {
        $this->authorizeJ4u();

        $term = PaymentTerm::findOrFail($paymentTermId);
        abort_unless($term->deal_id === $this->deal->id, 403);

        app(MarkPaymentTermPaidAction::class)->execute($term);

        $this->deal->refresh();
    }

    public function markMaterialReceived(int $materialDeadlineId): void
    {
        $this->authorizeJ4u();

        $deadline = MaterialDeadline::findOrFail($materialDeadlineId);
        abort_unless($deadline->deal_id === $this->deal->id, 403);

        app(MarkMaterialReceivedAction::class)->execute($deadline);

        $this->deal->refresh();
    }

    public function render(): View
    {
        $paymentTerms = $this->deal->paymentTerms;
        $materialDeadlines = $this->deal->materialDeadlines;

        return view('livewire.deal-show', [
            'totalPaid' => $paymentTerms->where('status', PaymentStatus::Paid)->sum('amount'),
            'totalTerms' => $paymentTerms->sum('amount'),
            'materialReceived' => $materialDeadlines->where('status', MaterialStatus::Received)->count(),
            'materialCount' => $materialDeadlines->count(),
        ]);
    }

    protected function authorizeJ4u(): void
    {
        abort_unless(auth()->user()->isJ4u(), 403);
    }
}
