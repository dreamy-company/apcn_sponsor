<?php

namespace App\DTOs\Catalog;

/**
 * Plain data carrier for package create/update.
 */
final readonly class PackageData
{
    /**
     * @param  list<int>  $itemIds
     */
    public function __construct(
        public string $name,
        public string $defaultPrice,
        public array $itemIds,
    ) {}
}
