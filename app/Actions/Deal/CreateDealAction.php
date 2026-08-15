<?php

namespace App\Actions\Deal;

use App\DTOs\Deal\DealData;
use App\Enums\DealStatus;
use App\Models\Deal;
use App\Models\Sponsor;
use Illuminate\Support\Facades\DB;

class CreateDealAction
{
    /**
     * Create a sponsor + draft deal with its items and payment terms atomically.
     */
    public function execute(DealData $data): Deal
    {
        return DB::transaction(function () use ($data): Deal {
            // A sponsor is the company entity: reuse an existing one by name so a
            // company's deals aggregate under a single sponsor. PIC is refreshed
            // to the latest details entered.
            $sponsor = Sponsor::firstOrNew(['company_name' => $data->companyName]);
            $sponsor->fill([
                'pic_name' => $data->picName,
                'pic_contact' => $data->picContact,
            ])->save();

            $deal = Deal::create([
                'deal_number' => $this->generateDealNumber(),
                'doctor_id' => $data->doctorId,
                'sponsor_id' => $sponsor->id,
                'package_id' => $data->packageId,
                'final_price' => $data->finalPrice,
                'status' => DealStatus::Draft,
            ]);

            $this->syncItems($deal, $data->items);

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
        $pivot = $this->toPivot($items);

        if ($pivot !== []) {
            $deal->items()->attach($pivot);
        }
    }

    /**
     * @param  array<int, array{item_id: int, is_addon: bool, custom_price: string|null}>  $items
     * @return array<int, array{is_addon: bool, custom_price: string|null}>
     */
    protected function toPivot(array $items): array
    {
        return collect($items)->mapWithKeys(fn (array $item): array => [
            $item['item_id'] => [
                'is_addon' => $item['is_addon'],
                'custom_price' => $item['custom_price'] !== '' && $item['custom_price'] !== null
                    ? $item['custom_price']
                    : null,
            ],
        ])->all();
    }

    protected function generateDealNumber(): string
    {
        $next = (Deal::max('id') ?? 0) + 1;

        return 'J4U-'.now()->format('Y').'-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
