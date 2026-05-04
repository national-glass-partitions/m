<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionFeatures\Plugin;

class AroundHasRequiredOptions
{
    /**
     * @param \Magento\Catalog\Model\Product\Type\AbstractType $subject
     * @param callable $proceed
     * @param \Magento\Catalog\Model\Product $product
     * @return bool
     */
    public function aroundHasRequiredOptions($subject, callable $proceed, $product)
    {
        if ($product->getMageworxIsRequire()) {
            return true;
        }

        return false;
    }
}