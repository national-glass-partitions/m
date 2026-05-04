<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionAdvancedPricing\Plugin;

use Magento\Catalog\Api\Data\ProductCustomOptionInterface;
use MageWorx\OptionAdvancedPricing\Helper\Data as Helper;

class CalculatePricePerCharacterOptionPlugin
{
    /**
     * @param ProductCustomOptionInterface $subject
     * @param mixed $result
     * @return float|int
     */
    public function afterGetPrice(ProductCustomOptionInterface $option, $price)
    {
        if ($option->getPriceType() !== Helper::PRICE_TYPE_PER_CHARACTER) {
            return $price;
        }

        $product = $option->getProduct();
        if (!$product) {
            return $price;
        }

        $customOptions = $product->getCustomOptions();
        if (!isset($customOptions['option_' . $option->getOptionId()])) {
            return $price;
        }

        $customOption = $customOptions['option_' . $option->getOptionId()];
        $price        = mb_strlen($customOption->getValue()) * $option->getDefaultPrice();

        return $price;
    }
}
