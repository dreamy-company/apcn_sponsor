<?php

namespace App\DTOs\Catalog;

/**
 * Plain data carrier for catalog item create/update.
 */
final readonly class ItemData
{
    /**
     * @param  string|null  $defaultPriceIdr  Rate-card price in IDR. NULL = quote on request.
     * @param  string|null  $defaultPriceUsd  Rate-card price in USD. NULL = quote on request.
     */
    public function __construct(
        public string $name,
        public ?string $type,
        public ?string $inclusion,
        public ?int $quota,
        public ?string $defaultPriceIdr,
        public ?string $defaultPriceUsd,
        public bool $requiresMaterial,
    ) {}
}
