<?php
/**
 * Copyright © 2018 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionFeatures\Plugin;

use MageWorx\OptionFeatures\Helper\Data as Helper;

class ModifyWishlistItemPrice
{
    protected Helper $helper;

    public function __construct(
        Helper $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * Update product price
     *
     * @param $subject
     * @param \Magento\Catalog\Model\Product $product
     * @return \Magento\Catalog\Model\Product
     */
    public function afterGetProduct($subject, $product)
    {
        if (!$this->validate($product)) {
            return $product;
        }

        $product->setPrice(0);

        return $product;
    }

    /**
     * Validate product and configuration
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return bool
     */
    protected function validate($product)
    {
        if (!$this->helper->isAbsolutePriceEnabled()) {
            return false;
        }

        if (!$product->getData('absolute_price')) {
            return false;
        }

        if (!$product->hasCustomOptions()) {
            return false;
        }

        $optionIds = $product->getCustomOption('option_ids');
        if (!$optionIds) {
            return false;
        }

        return true;
    }
}
