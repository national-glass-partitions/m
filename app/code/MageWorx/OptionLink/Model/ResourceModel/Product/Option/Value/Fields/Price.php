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
 * Class Price. Used to modify "Price" field in sql.
 */
class Price implements FieldInterface
{
    /**
     * Add product attribute "Price" to Option Value Collection.
     *
     * @param Collection $collection
     */
    public function addField(Collection $collection): void
    {
        $productTable = CollectionUpdater::KEY_TABLE_OPTIONLINK_PRODUCT;

        $priceExpr = $collection->getConnection()->getCheckSql(
            'store_value_price.price IS NULL',
            'default_value_price.price',
            'store_value_price.price'
        );

        $collection->getSelect()->columns(
            [
                'price' => 'IF('.
                            'main_table.sku IS NULL, '.
                            $priceExpr.', IF('.$productTable.'.sku IS NULL, '.$priceExpr.', '.$productTable.'.price)'.
                            ')'
            ]
        );
    }
}
