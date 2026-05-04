<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionInventory\Model\ResourceModel\Product\Option;

use Magento\Framework\App\ResourceConnection;

class ManageStockOptionCollection
{
    protected ResourceConnection $resourceConnection;

    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    public function getOptionsContainsManageStockValues(array $optionIds): array
    {
        if(empty($optionIds)) {
            return [];
        }

        $connection = $this->resourceConnection->getConnection();
        $select     = $connection->select()->distinct()->from(
            ['cpotv' => $this->resourceConnection->getTableName('catalog_product_option_type_value')],
            ['option_id']
        )->where(
            'option_id IN (' . implode(',', $optionIds) . ')'
        )->where(
            'cpotv.manage_stock = 1'
        );

        return $connection->fetchCol($select);
    }
}
