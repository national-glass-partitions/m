<?php

/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionBase\Plugin\Adminhtml;

use Magento\ConfigurableProduct\Helper\Product\Options\Loader;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\ConfigurableProduct\Api\Data\OptionInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

class EscapeNonConfigurableLoad
{
    /**
     * Fix Magento 2.3.0 issue when configurable contains children of virtual product type
     *
     * @param Loader $subject
     * @param \Closure $proceed
     * @param ProductInterface $product
     * @return OptionInterface[]
     */
    public function aroundLoad($subject, \Closure $proceed, ProductInterface $product)
    {
        $typeInstance = $product->getTypeInstance();
        if (!($typeInstance instanceof Configurable)) {
            return [];
        }
        return $proceed($product);
    }
}
