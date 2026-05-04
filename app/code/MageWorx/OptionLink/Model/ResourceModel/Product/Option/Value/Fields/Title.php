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
 * Class Title. Used to modify "Title" field in sql.
 */
class Title implements FieldInterface
{
    /**
     * Add product attribute "Title" to Option Value Collection.
     *
     * @param Collection $collection
     */
    public function addField(Collection $collection): void
    {
        $productTable = CollectionUpdater::KEY_TABLE_OPTIONLINK_PRODUCT;

        $titleExpr = $collection->getConnection()->getCheckSql(
            'store_value_title.title IS NULL',
            'default_value_title.title',
            'store_value_title.title'
        );

        $collection->getSelect()->columns(
            ['title' => 'IF('.
                            'main_table.sku IS NULL, '.
                            $titleExpr.', IF('.$productTable.'.sku IS NULL, '.$titleExpr.', '.$productTable.'.name)'.
                        ')'
            ]
        );
    }
}
