<?php

namespace App\Services;

use App\Enums\DealStatus;
use App\Enums\MaterialStatus;
use App\Enums\PaymentStatus;
use App\Models\Deal;
use App\Models\MaterialDeadline;
use App\Models\PaymentTerm;
use Illuminate\Support\Collection;

class DashboardService
{
    /**
     * Role-scoped aggregate summary. Pass null for global (J4U) or a doctor id to scope.
     *
     * @return array{
     *     dealsCount: int,
     *     draftCount: int,
     *     finalizedCount: int,
     *     totalCommitted: string,
     *     paidAmount: string,
     *     outstandingAmount: string,
     *     materialReceived: int,
     *     materialTotal: int,
     * }
     */
    public function summary(?int $doctorId = null): array
    {
        $deals = Deal::query()
            ->when($doctorId !== null, fn ($q) => $q->where('doctor_id', $doctorId))
            ->get(['id', 'status', 'final_price']);

        $dealIds = $deals->pluck('id');

        $terms = $dealIds->isEmpty()
            ? collect()
            : PaymentTerm::whereIn('deal_id', $dealIds)->get(['amount', 'status']);

        $materials = $dealIds->isEmpty()
            ? collect()
            : MaterialDeadline::whereIn('deal_id', $dealIds)->get(['status']);

        return [
            'dealsCount' => $deals->count(),
            'draftCount' => $deals->where('status', DealStatus::Draft)->count(),
            'finalizedCount' => $deals->where('status', DealStatus::Finalized)->count(),
            'totalCommitted' => (string) $deals->where('status', DealStatus::Finalized)->sum('final_price'),
            'paidAmount' => (string) $terms->where('status', PaymentStatus::Paid)->sum('amount'),
            'outstandingAmount' => (string) $terms->where('status', PaymentStatus::Pending)->sum('amount'),
            'materialReceived' => $materials->where('status', MaterialStatus::Received)->count(),
            'materialTotal' => $materials->count(),
        ];
    }

    /**
     * @return Collection<int, Deal>
     */
    public function recentDeals(?int $doctorId = null, int $limit = 5): Collection
    {
        return Deal::query()
            ->with(['sponsor', 'doctor', 'package'])
            ->when($doctorId !== null, fn ($q) => $q->where('doctor_id', $doctorId))
            ->latest()
            ->limit($limit)
            ->get();
    }
}
