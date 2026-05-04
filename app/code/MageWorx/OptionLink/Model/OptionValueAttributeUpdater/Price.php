<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See https://www.mageworx.com/terms-and-conditions for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionLink\Model\OptionValueAttributeUpdater;

use Magento\Catalog\Model\Product;
use MageWorx\OptionLink\Model\OptionValueAttributeUpdaterInterface;
use MageWorx\OptionLink\Model\ResourceModel\Product\Option\Value\Price as PriceResource;

class Price implements OptionValueAttributeUpdaterInterface
{
    protected PriceResource $priceResource;
    public function __construct(PriceResource $priceResource)
    {
        $this->priceResource = $priceResource;
    }

    /**
     * @param array $optionTypeIds
     * @param Product $product
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function process(array $optionTypeIds, Product $product): bool
    {
        return $this->priceResource->updateValuesByProductId($optionTypeIds, (int)$product->getId());
    }
}
