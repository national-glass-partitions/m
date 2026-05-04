<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionBase\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface as Connection;
use MageWorx\OptionBase\Helper\Data;

class DataSaver
{
    protected ResourceConnection $resource;
    protected Connection $connection;
    protected Data $baseHelper;

    public function __construct(
        ResourceConnection $resource,
        Data $helperData
    ) {
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
        $this->baseHelper = $helperData;
    }

    /**
     * Delete option/option value data by certain condition
     *
     * @param string $tableName
     * @param string $condition
     * @return void
     */
    public function deleteData($tableName, $condition)
    {
        $this->connection->delete($this->resource->getTableName($tableName), $condition);
    }

    /**
     * Insert multiple option/option value data
     *
     * @param string $tableName
     * @param array $data
     * @return void
     */
    public function insertMultipleData($tableName, $data)
    {
        $this->connection->insertMultiple($this->resource->getTableName($tableName), $data);
    }

    /**
     * Update table catalog_product_entity
     *
     * @param $productId
     * @param $isRequire
     */
    public function updateValueIsRequire($productId, $isRequire)
    {
        $columnIdName = $this->baseHelper->isEnterprise() ? 'row_id' : 'entity_id';
        $where        = [$columnIdName . '=' . $productId];
        $this->connection->update(
            $this->resource->getTableName('catalog_product_entity'),
            ['mageworx_is_require' => $isRequire],
            $where
        );
    }
}
