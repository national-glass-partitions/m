<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionFeatures\Model\Attribute\Product;

use MageWorx\OptionFeatures\Helper\Data as Helper;
use MageWorx\OptionBase\Model\Product\AbstractProductAttribute;
use MageWorx\OptionBase\Api\ProductAttributeInterface;

class HideAdditionalProductPrice extends AbstractProductAttribute implements ProductAttributeInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return Helper::KEY_HIDE_ADDITIONAL_PRODUCT_PRICE;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriorityValue()
    {
        return 1;
    }
}
