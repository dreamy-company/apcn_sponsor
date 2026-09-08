<?php

namespace App\DTOs\Catalog;

/**
 * Plain data carrier for package create/update.
 */
final readonly class PackageData
{
    /**
     * @param  string|null  $defaultPriceIdr  Tier price in IDR.
     * @param  string|null  $defaultPriceUsd  Tier price in USD.
     * @param  array<int, int>  $items  item id => units the tier includes (>= 1).
     */
    public function __construct(
        public string $name,
        public ?string $defaultPriceIdr,
        public ?string $defaultPriceUsd,
        public ?int $quota,
        public array $items,
    ) {}

    /**
     * Pivot payload for `package_item`.
     *
     * @return array<int, array{quantity: int}>
     */
    public function itemPivot(): array
    {
        $pivot = [];

        foreach ($this->items as $itemId => $quantity) {
            $pivot[(int) $itemId] = ['quantity' => max(1, (int) $quantity)];
        }

        return $pivot;
    }
}
