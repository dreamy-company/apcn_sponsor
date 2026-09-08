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
            $sponsor = $this->resolveSponsor($data);

            $deal = Deal::create([
                'deal_number' => $this->generateDealNumber(),
                'doctor_id' => $data->doctorId,
                'sponsor_id' => $sponsor->id,
                'package_id' => $data->packageId,
                'currency' => $data->currency,
                'subtotal' => $data->subtotal,
                'inclusion' => $data->inclusion,
                'final_price' => $data->finalPrice,
                'status' => DealStatus::Draft,
            ]);

            $this->syncItems($deal, $data->items);

            foreach ($data->paymentTerms as $term) {
                $deal->paymentTerms()->create([
                    'description' => $term['description'],
                    'due_date' => $term['due_date'],
                    'amount' => $term['amount'],
                    'notes' => $term['notes'] ?? null,
                ]);
            }

            return $deal;
        });
    }

    /**
     * A sponsor is one brand under one legal entity: deals aggregate under the
     * (company_name, brand_name) pair, so two brands of the same PT stay separate.
     * PIC is refreshed to the latest details entered.
     */
    protected function resolveSponsor(DealData $data): Sponsor
    {
        $sponsor = Sponsor::firstOrNew([
            'company_name' => $data->companyName,
            'brand_name' => $data->brandName,
        ]);

        $sponsor->fill([
            'pic_name' => $data->picName,
            'pic_contact' => $data->picContact,
        ])->save();

        return $sponsor;
    }

    /**
     * @param  array<int, array{item_id: int, quantity: int, is_addon: bool, custom_price: string|null}>  $items
     */
    protected function syncItems(Deal $deal, array $items): void
    {
        $pivot = $this->toPivot($items);

        if ($pivot !== []) {
            $deal->items()->attach($pivot);
        }
    }

    /**
     * @param  array<int, array{item_id: int, quantity: int, is_addon: bool, custom_price: string|null}>  $items
     * @return array<int, array{quantity: int, is_addon: bool, custom_price: string|null}>
     */
    protected function toPivot(array $items): array
    {
        return collect($items)->mapWithKeys(fn (array $item): array => [
            $item['item_id'] => [
                'quantity' => max(1, $item['quantity']),
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
