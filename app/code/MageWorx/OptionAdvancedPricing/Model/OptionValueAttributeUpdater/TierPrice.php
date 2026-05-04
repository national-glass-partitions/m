<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See https://www.mageworx.com/terms-and-conditions for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionAdvancedPricing\Model\OptionValueAttributeUpdater;

use Magento\Catalog\Model\Product;
use MageWorx\OptionLink\Model\OptionValueAttributeUpdaterInterface;
use MageWorx\OptionAdvancedPricing\Model\ResourceModel\TierPrice as TierPriceResource;

class TierPrice implements OptionValueAttributeUpdaterInterface
{
    protected TierPriceResource $tierPriceResource;

    public function __construct(TierPriceResource $tierPriceResource)
    {
        $this->tierPriceResource = $tierPriceResource;
    }

    /**
     * @param array $optionTypeIds
     * @param Product $product
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function process(array $optionTypeIds, Product $product): bool
    {
        return $this->tierPriceResource->updateValuesByProductId($optionTypeIds, (int)$product->getId());
    }
}
