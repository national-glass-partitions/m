<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace MageWorx\DynamicOptionsBase\Api;

interface CalculateDynamicOptionsPriceInterface
{
    /**
     * @param \Magento\Quote\Api\Data\CartItemInterface $item
     * @return float|null
     */
    public function execute(\Magento\Catalog\Api\Data\ProductInterface $item): float;
}
