<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionLink\Model\ResourceModel\Product\Option\Value\Fields;

use \MageWorx\OptionLink\Model\ResourceModel\Product\Option\Value\CollectionUpdater;
use \MageWorx\OptionLink\Model\ResourceModel\Product\Option\Value\FieldInterface;
use \Magento\Catalog\Model\ResourceModel\Product\Option\Value\Collection;

/**
 * Class Weight. Used to modify "Weight" field in sql.
 */
class Weight implements FieldInterface
{
    /**
     * Add product attribute "Weight" to Option Value Collection.
     *
     * @param Collection $collection
     */
    public function addField(Collection $collection): void
    {
        $productTable = CollectionUpdater::KEY_TABLE_OPTIONLINK_PRODUCT;

        $collection->getSelect()->columns(
            'IF('.
                'main_table.sku IS NULL, '.
                'main_table.weight, '.
                'IF('.$productTable.'.sku IS NULL, main_table.weight, '.$productTable.'.weight)'.
            ') as weight'
        );
    }
}
