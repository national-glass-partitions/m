<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See https://www.mageworx.com/terms-and-conditions for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionLink\Model\ResourceModel\Product\Option\Value;

use Magento\Framework\App\ResourceConnection;
use MageWorx\OptionVisibility\Model\OptionStoreView as StoreViewModel;

class SkuIsValidUpdater
{
    protected ResourceConnection $resourceConnection;

    /**
     * ProductPathCollection constructor.
     *
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    public function updateOptionTypeIdByValidProductSkus(bool $skuIsValid, string $sku): void
    {
        $updateMap  = [
            'catalog_product_option_type_value'                => null,
            'mageworx_optiontemplates_group_option_type_value' => null
        ];
        $connection = $this->resourceConnection->getConnection();
        foreach ($updateMap as $tableName => $data) {
            $select = $connection->select()->from(
                ['cpotv' => $this->resourceConnection->getTableName($tableName)],
                ['option_type_id']
            )->where(
                'cpotv.sku = ?',
                $sku
            )->where(
                'cpotv.sku_is_valid != ' . (int)$skuIsValid
            );

            $updateMap[$tableName] = $connection->fetchCol($select);
        }
        $this->updateSkuIsValidData($updateMap, $skuIsValid);
    }

    /**
     * update sku_is_valid attribute on setup patch
     */
    public function updateOptionTypeIdByValidProductSkusOnSetup(bool $skuIsValid): void
    {
        $updateMap = [
            'catalog_product_option_type_value'                => null,
            'mageworx_optiontemplates_group_option_type_value' => null
        ];

        $connection = $this->resourceConnection->getConnection();
        foreach ($updateMap as $tableName => &$optionTypeIds) {
            $select = $connection->select()->from(
                ['cpotv' => $this->resourceConnection->getTableName($tableName)],
                ['option_type_id']
            )->join(
                ['cpe' => $this->resourceConnection->getTableName('catalog_product_entity')],
                'cpe.sku = cpotv.sku',
                []
            )->where(
                'cpe.sku != ""'
            );

            $optionTypeIds = $connection->fetchCol($select);
        }
        $this->updateSkuIsValidData($updateMap, $skuIsValid);

    }

    /**
     * update sku_is_valid attribute
     */
    public function updateSkuIsValidData(array $updateMap, bool $skuIsValid): void
    {
        foreach ($updateMap as $tableName => $optionTypeIds) {
            $this->resourceConnection->getConnection()->update(
                $this->resourceConnection->getTableName($tableName),
                ['sku_is_valid' => (int)$skuIsValid],
                'option_type_id IN (' . "'" . implode("','", $optionTypeIds) . "'" . ')'
            );
        }
    }
}
