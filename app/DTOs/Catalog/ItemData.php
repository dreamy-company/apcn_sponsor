<?php

namespace App\DTOs\Catalog;

/**
 * Plain data carrier for catalog item create/update.
 */
final readonly class ItemData
{
    public function __construct(
        public string $name,
        public ?string $type,
        public ?int $quota,
        public ?string $defaultPrice,
        public bool $requiresMaterial,
    ) {}
}
