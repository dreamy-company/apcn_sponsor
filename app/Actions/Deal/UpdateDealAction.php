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

            // Resolve the sponsor by company name (it may have changed). A company
            // is a single sponsor, so repoint the deal rather than renaming a shared
            // sponsor. PIC is refreshed to the latest details.
            $sponsor = Sponsor::firstOrNew(['company_name' => $data->companyName]);
            $sponsor->fill([
                'pic_name' => $data->picName,
                'pic_contact' => $data->picContact,
            ])->save();

            $deal->update([
                'doctor_id' => $data->doctorId,
                'sponsor_id' => $sponsor->id,
                'package_id' => $data->packageId,
                'final_price' => $data->finalPrice,
            ]);

            // Clean up the previous sponsor if it is now orphaned.
            if ($previousSponsorId !== $sponsor->id) {
                Sponsor::whereKey($previousSponsorId)->whereDoesntHave('deals')->delete();
            }

            $this->syncItems($deal, $data->items);

            $deal->paymentTerms()->delete();

            foreach ($data->paymentTerms as $term) {
                $deal->paymentTerms()->create($term);
            }

            return $deal;
        });
    }

    /**
     * @param  array<int, array{item_id: int, is_addon: bool, custom_price: string|null}>  $items
     */
    protected function syncItems(Deal $deal, array $items): void
    {
        $pivot = collect($items)->mapWithKeys(fn (array $item): array => [
            $item['item_id'] => [
                'is_addon' => $item['is_addon'],
                'custom_price' => $item['custom_price'] !== '' && $item['custom_price'] !== null
                    ? $item['custom_price']
                    : null,
            ],
        ])->all();

        $deal->items()->sync($pivot);
    }
}
