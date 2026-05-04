<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionDependency\Model\ResourceModel;

class Config extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize main table and table id field
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\MageWorx\OptionDependency\Model\Config::TABLE_NAME, 'dependency_id');
    }

    public function allProductOptions($productId)
    {
        $connection = $this->getConnection();

        $select = $connection->select()->from(
            $this->getTable('catalog_product_option'),
            ['option_id', 'option_id']
        )->where(
            'product_id = ' . $productId
        );

        return $connection->fetchPairs($select);
    }

    /**
     * Load array of dependencies by $columns.
     * Dependencies can be:
     * 1. 'dp_child_option_id' => 'dp_parent_option_type_id'
     * 2. 'dp_child_option_type_id' => 'dp_parent_option_type_id'
     * Then the result array processed in the Model\Config.
     *
     * @param int|array $productId
     * @param array $columns
     * @return array
     */
    public function loadDependencies($productId, $columns = [])
    {
        $connection = $this->getConnection();

        $select = $connection->select()->from(
            $this->getTable(\MageWorx\OptionDependency\Model\Config::TABLE_NAME),
            $columns
        )->where(
            'product_id = ' . $productId
        );

        return $connection->fetchAll($select);
    }

    /**
     * Load array of option types.
     * ['option_id' => 'type']
     *
     * @param int $productId
     * @return array
     */
    public function loadOptionTypes($productId)
    {
        $connection = $this->getConnection();

        $select = $connection->select()->from(
            $this->getTable('catalog_product_option'),
            ['option_id', 'type']
        )->where(
            'product_id = ' . $productId
        );

        return $connection->fetchPairs($select);
    }

    /**
     * Load option_id array by option_id array
     *
     * @param array $ids
     * @return array
     */
    public function loadOptionId($ids = [])
    {
        $connection = $this->getConnection();

        $select = $connection->select()->from(
            $this->getTable('catalog_product_option'),
            ['option_id', 'option_id']
        )->where(
            'option_id IN (?)',
            $ids
        );

        return $connection->fetchPairs($select);
    }

    /**
     * Load option_type_id array by option_type_id array
     *
     * @param array $ids
     * @return array
     */
    public function loadOptionTypeId($ids = [])
    {
        $connection = $this->getConnection();

        $select = $connection->select()->from(
            $this->getTable('catalog_product_option_type_value'),
            ['option_type_id', 'option_type_id']
        )->where(
            'option_type_id IN (?)',
            $ids
        );

        return $connection->fetchPairs($select);
    }

    /**
     *
     * @param array $parentValueId
     * @return array
     */
    public function getIsDisabledData($parentValueId = [])
    {
        $select = $this->getConnection()
                       ->select()
                       ->from(
                           ['main_table' => $this->getTable('catalog_product_option_type_value')],
                           ['disabled']
                       )
                       ->join(
                           ['dependency_table' => $this->getTable('mageworx_option_dependency')],
                           'main_table.option_type_id = dependency_table.dp_parent_option_type_id',
                           ['dp_parent_option_type_id']
                       )
                       ->where('dependency_table.dp_parent_option_type_id IN (?)', $parentValueId);

        return $this->getConnection()->fetchAll($select);
    }
}
