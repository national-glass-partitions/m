<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionFeatures\Model\Attribute\Option;


use MageWorx\OptionBase\Model\Product\Option\AbstractAttribute;
use MageWorx\OptionFeatures\Helper\Data as Helper;

class HideProductPageValuePrice extends AbstractAttribute
{
    public function getName(): string
    {
        return Helper::KEY_HIDE_PRODUCT_PAGE_VALUE_PRICE;
    }
}
