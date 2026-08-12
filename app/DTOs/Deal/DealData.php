<?php

namespace App\DTOs\Deal;

/**
 * Plain data carrier for deal creation/update. No Model dependencies.
 */
final readonly class DealData
{
    /**
     * @param  int  $doctorId  Doctor who initiated the deal.
     * @param  string  $companyName  Sponsor company name.
     * @param  string  $picName  Sponsor PIC name.
     * @param  string  $picContact  Sponsor PIC contact.
     * @param  int|null  $packageId  Base package (tier), if any.
     * @param  string  $finalPrice  Agreed final price (IDR).
     * @param  array<int, array{item_id: int, is_addon: bool, custom_price: string|null}>  $items
     * @param  array<int, array{description: string, due_date: string, amount: string}>  $paymentTerms
     */
    public function __construct(
        public int $doctorId,
        public string $companyName,
        public string $picName,
        public string $picContact,
        public ?int $packageId,
        public string $finalPrice,
        public array $items,
        public array $paymentTerms,
    ) {}
}
