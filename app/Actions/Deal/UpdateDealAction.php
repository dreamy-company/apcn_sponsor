<?php

namespace App\Actions\Deal;

use App\DTOs\Deal\DealData;
use App\Models\Deal;
use App\Models\Sponsor;
use Illuminate\Support\Facades\DB;

class UpdateDealAction
{
    /**
     * Update a draft deal: sponsor info, base package, items, and payment terms.
     */
    public function execute(Deal $deal, DealData $data): Deal
    {
        return DB::transaction(function () use ($deal, $data): Deal {
            $previousSponsorId = $deal->sponsor_id;

            // Resolve the sponsor by (company, brand) — it may have changed. A brand
            // is a single sponsor, so repoint the deal rather than renaming a shared
            // sponsor. PIC is refreshed to the latest details.
            $sponsor = Sponsor::firstOrNew([
                'company_name' => $data->companyName,
                'brand_name' => $data->brandName,
            ]);
            $sponsor->fill([
                'pic_name' => $data->picName,
                'pic_contact' => $data->picContact,
            ])->save();

            $deal->update([
                'doctor_id' => $data->doctorId,
                'sponsor_id' => $sponsor->id,
                'package_id' => $data->packageId,
                'currency' => $data->currency,
                'subtotal' => $data->subtotal,
                'inclusion' => $data->inclusion,
                'final_price' => $data->finalPrice,
            ]);

            // Clean up the previous sponsor if it is now orphaned.
            if ($previousSponsorId !== $sponsor->id) {
                Sponsor::whereKey($previousSponsorId)->whereDoesntHave('deals')->delete();
            }

            $this->syncItems($deal, $data->items);
            $this->syncPaymentTerms($deal, $data->paymentTerms);

            return $deal;
        });
    }

    /**
     * @param  array<int, array{item_id: int, quantity: int, is_addon: bool, custom_price: string|null}>  $items
     */
    protected function syncItems(Deal $deal, array $items): void
    {
        $pivot = collect($items)->mapWithKeys(fn (array $item): array => [
            $item['item_id'] => [
                'quantity' => max(1, $item['quantity']),
                'is_addon' => $item['is_addon'],
                'custom_price' => $item['custom_price'] !== '' && $item['custom_price'] !== null
                    ? $item['custom_price']
                    : null,
            ],
        ])->all();

        $deal->items()->sync($pivot);
    }

    /**
     * Reconcile payment terms in place. Rows carrying an existing id are updated,
     * so a term's paid status, transfer proof and verification survive an edit;
     * only genuinely removed rows are deleted.
     *
     * @param  array<int, array{id: int|null, description: string, due_date: string, amount: string, notes: string|null}>  $terms
     */
    protected function syncPaymentTerms(Deal $deal, array $terms): void
    {
        $keptIds = [];

        foreach ($terms as $term) {
            $attributes = [
                'description' => $term['description'],
                'due_date' => $term['due_date'],
                'amount' => $term['amount'],
                'notes' => $term['notes'] ?? null,
            ];

            $existing = $term['id'] !== null
                ? $deal->paymentTerms()->whereKey($term['id'])->first()
                : null;

            if ($existing !== null) {
                $existing->update($attributes);
                $keptIds[] = $existing->id;

                continue;
            }

            $keptIds[] = $deal->paymentTerms()->create($attributes)->id;
        }

        $deal->paymentTerms()->whereKeyNot($keptIds)->delete();
    }
}
