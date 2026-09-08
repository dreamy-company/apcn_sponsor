<?php

namespace App\Services;

use App\Enums\Currency;
use App\Enums\DealStatus;
use App\Enums\MaterialStatus;
use App\Enums\PaymentStatus;
use App\Models\Deal;
use App\Models\Item;
use App\Models\MaterialDeadline;
use App\Models\Package;
use App\Models\PaymentTerm;
use Illuminate\Support\Collection;

class DashboardService
{
    /**
     * Role-scoped aggregate summary. Pass null for global (J4U) or a doctor id to scope.
     *
     * Money is never summed across currencies: the top-level totals are IDR, and
     * `usd` carries the same three figures for USD deals. Callers render both.
     *
     * @return array{
     *     dealsCount: int,
     *     draftCount: int,
     *     finalizedCount: int,
     *     totalCommitted: string,
     *     paidAmount: string,
     *     outstandingAmount: string,
     *     usd: array{totalCommitted: string, paidAmount: string, outstandingAmount: string},
     *     materialReceived: int,
     *     materialTotal: int,
     * }
     */
    public function summary(?int $doctorId = null): array
    {
        $deals = Deal::query()
            ->when($doctorId !== null, fn ($q) => $q->where('doctor_id', $doctorId))
            ->get(['id', 'status', 'currency', 'final_price']);

        $dealIds = $deals->pluck('id');

        $terms = $dealIds->isEmpty()
            ? collect()
            : PaymentTerm::whereIn('deal_id', $dealIds)->get(['deal_id', 'amount', 'status']);

        $materials = $dealIds->isEmpty()
            ? collect()
            : MaterialDeadline::whereIn('deal_id', $dealIds)->get(['status']);

        // deal_id => currency, so each term is attributed to its deal's currency.
        $currencyOf = $deals->pluck('currency', 'id');

        $committed = fn (Currency $c): string => (string) $deals
            ->where('status', DealStatus::Finalized)
            ->where('currency', $c)
            ->sum('final_price');

        $termTotal = fn (Currency $c, PaymentStatus $status): string => (string) $terms
            ->where('status', $status)
            ->filter(fn (PaymentTerm $t): bool => ($currencyOf[$t->deal_id] ?? Currency::IDR) === $c)
            ->sum('amount');

        return [
            'dealsCount' => $deals->count(),
            'draftCount' => $deals->where('status', DealStatus::Draft)->count(),
            'finalizedCount' => $deals->where('status', DealStatus::Finalized)->count(),
            'totalCommitted' => $committed(Currency::IDR),
            'paidAmount' => $termTotal(Currency::IDR, PaymentStatus::Paid),
            'outstandingAmount' => $termTotal(Currency::IDR, PaymentStatus::Pending),
            'usd' => [
                'totalCommitted' => $committed(Currency::USD),
                'paidAmount' => $termTotal(Currency::USD, PaymentStatus::Paid),
                'outstandingAmount' => $termTotal(Currency::USD, PaymentStatus::Pending),
            ],
            'materialReceived' => $materials->where('status', MaterialStatus::Received)->count(),
            'materialTotal' => $materials->count(),
        ];
    }

    /**
     * Sponsorship inventory nearest to selling out — quota-bearing items and
     * packages, ordered by how few slots remain. Counted from finalized deals
     * only, via QuotaService, so it agrees with the wizard and catalog pages.
     *
     * @return list<array{kind: string, name: string, taken: int, quota: int, remaining: int, url: string}>
     */
    public function inventory(int $limit = 8): array
    {
        $quota = app(QuotaService::class);
        $rows = [];

        foreach (Item::query()->whereNotNull('quota')->get() as $item) {
            $taken = $quota->itemTakenCount($item->id);
            $cap = (int) $item->quota;

            $rows[] = [
                'kind' => 'item',
                'name' => $item->name,
                'taken' => $taken,
                'quota' => $cap,
                'remaining' => max(0, $cap - $taken),
                'url' => route('catalog.items.show', $item),
            ];
        }

        foreach (Package::query()->whereNotNull('quota')->get() as $package) {
            $taken = $quota->packageTakenCount($package->id);
            $cap = (int) $package->quota;

            $rows[] = [
                'kind' => 'package',
                'name' => $package->name,
                'taken' => $taken,
                'quota' => $cap,
                'remaining' => max(0, $cap - $taken),
                'url' => route('catalog.packages.show', $package),
            ];
        }

        usort($rows, fn (array $a, array $b): int => [$a['remaining'], $a['name']] <=> [$b['remaining'], $b['name']]);

        return array_slice($rows, 0, $limit);
    }

    /**
     * @return Collection<int, Deal>
     */
    public function recentDeals(?int $doctorId = null, int $limit = 5): Collection
    {
        return Deal::query()
            ->with(['sponsor', 'doctor', 'package'])
            ->withSum(['paymentTerms as paid_total' => fn ($q) => $q->where('status', PaymentStatus::Paid)], 'amount')
            ->when($doctorId !== null, fn ($q) => $q->where('doctor_id', $doctorId))
            ->latest()
            ->limit($limit)
            ->get();
    }
}
