<?php

namespace App\DTOs\Deal;

/**
 * Plain data carrier for deal creation/update. No Model dependencies.
 */
final readonly class DealData
{
    /**
     * @param  int  $doctorId  Doctor who initiated the deal.
     * @param  string  $companyName  Sponsor legal entity (PT) name.
     * @param  string  $brandName  Brand negotiated under that entity — one per deal.
     * @param  string  $picName  Sponsor PIC name.
     * @param  string  $picContact  Sponsor PIC contact.
     * @param  int|null  $packageId  Base package (tier), if any.
     * @param  string  $currency  Currency code the deal is transacted in (IDR|USD).
     * @param  string  $subtotal  Accumulated rate-card value of package + add-ons (BR-07).
     * @param  string  $finalPrice  Agreed final price — authoritative, manually entered (BR-02).
     * @param  array<int, array{item_id: int, quantity: int, inclusion: string|null, is_addon: bool, custom_price: string|null}>  $items
     * @param  array<int, array{id: int|null, description: string, due_date: string, amount: string, notes: string|null}>  $paymentTerms
     */
    public function __construct(
        public int $doctorId,
        public string $companyName,
        public string $brandName,
        public string $picName,
        public string $picContact,
        public ?int $packageId,
        public string $currency,
        public string $subtotal,
        public string $finalPrice,
        public array $items,
        public array $paymentTerms,
    ) {}
}
