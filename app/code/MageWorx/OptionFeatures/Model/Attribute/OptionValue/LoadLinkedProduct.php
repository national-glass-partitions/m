<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionFeatures\Model\Attribute\OptionValue;


use MageWorx\OptionBase\Model\Product\Option\AbstractAttribute;
use MageWorx\OptionFeatures\Helper\Data as Helper;

class LoadLinkedProduct extends AbstractAttribute
{

    /**
     * @return string
     */
    public function getName()
    {
        return Helper::KEY_LOAD_LINKED_PRODUCT;
    }
}
