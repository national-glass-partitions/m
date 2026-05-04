<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionLink\Model\ResourceModel\Product\Option\Value;

use \Magento\Catalog\Model\ResourceModel\Product\Option\Value\Collection;

interface FieldInterface
{
    public function addField(Collection $collection): void;
}
