<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See https://www.mageworx.com/terms-and-conditions for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionLink\Model;

use Magento\Catalog\Model\ResourceModel\Product as ProductResource;

class OptionValueSkuVlidator
{
    protected ProductResource $productResource;

    /**
     * SkuVlidator constructor.
     *
     * @param ProductResource $productResource
     */
    public function __construct(
        ProductResource $productResource
    ) {
        $this->productResource = $productResource;
    }

    public function isOptionValueSkuIsValid($sku): bool
    {
        return $this->productResource->getIdBySku($sku) ? true : false;
    }
}
