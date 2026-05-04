<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See https://www.mageworx.com/terms-and-conditions for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionAdvancedPricing\Model\OptionValueAttributeUpdater;

use Magento\Catalog\Model\Product;
use MageWorx\OptionLink\Model\OptionValueAttributeUpdaterInterface;
use MageWorx\OptionAdvancedPricing\Model\ResourceModel\SpecialPrice as SpecialPriceResource;

class SpecialPrice implements OptionValueAttributeUpdaterInterface
{
    protected SpecialPriceResource $specialPriceResource;

    public function __construct(SpecialPriceResource $specialPriceResource)
    {
        $this->specialPriceResource = $specialPriceResource;
    }

    /**
     * @param array $optionTypeIds
     * @param Product $product
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function process(array $optionTypeIds, Product $product): bool
    {
        return $this->specialPriceResource->updateValuesByProductId($optionTypeIds, (int)$product->getId());
    }
}
