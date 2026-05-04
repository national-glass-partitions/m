<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionFeatures\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;

class BundleSelected
{
    protected ResourceConnection $resource;

    /**
     * @param ManagerInterface $eventManager
     * @param Helper $helper
     * @param AdvancedPricingPrice $advancedPricingPrice
     */
    public function __construct(
        ResourceConnection $resource
    ) {
        $this->resource = $resource;
    }

    public function getBundleSelectedData(string $selectedIds): array
    {
        $connection = $this->resource->getConnection();
        $select     = $connection->select();
        $select
            ->from(
                $this->resource->getTableName('catalog_product_bundle_selection'),
                ['selection_id', 'selection_price_value', 'option_id', 'selection_can_change_qty', 'selection_qty']
            )
            ->where('selection_id IN (' . $selectedIds . ')');

        return $connection->fetchAll($select);
    }
}
