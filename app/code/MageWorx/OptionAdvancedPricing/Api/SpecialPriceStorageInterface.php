<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionAdvancedPricing\Api;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductCustomOptionValuesInterface;

interface SpecialPriceStorageInterface
{
    /**
     * Get special price JSON data from storage. Load by product id if not loaded yet.
     * Returns null if no data is found.
     *
     * @param ProductInterface $product
     * @param ProductCustomOptionValuesInterface $value
     * @return string|null
     */
    public function getSpecialPriceData(ProductInterface $product, ProductCustomOptionValuesInterface $value): ?string;

    /**
     * Load pricing data by specific product id
     *
     * @param ProductInterface $product
     * @return void
     */
    public function loadData(ProductInterface $product): void;
}
