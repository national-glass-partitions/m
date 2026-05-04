<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionTemplates\Model\ResourceModel\Group\Option\Value;

/**
 * Group option values collection
 */
class Collection extends \Magento\Catalog\Model\ResourceModel\Product\Option\Value\Collection
{
    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'MageWorx\OptionTemplates\Model\Group\Option\Value',
            'MageWorx\OptionTemplates\Model\ResourceModel\Group\Option\Value'
        );
    }

    /**
     * Retrieve table name
     * Replace product option tables to mageworx group option tables
     *
     * @param string $origTableName
     * @return string
     *
     */
    public function getTable($origTableName)
    {
        $origTableName = parent::getTable($origTableName);

        switch ($origTableName) {
            case parent::getTable('catalog_product_option'):
                $tableName = 'mageworx_optiontemplates_group_option';
                break;
            case parent::getTable('catalog_product_option_title'):
                $tableName = 'mageworx_optiontemplates_group_option_title';
                break;
            case parent::getTable('catalog_product_option_price'):
                $tableName = 'mageworx_optiontemplates_group_option_price';
                break;
            case parent::getTable('catalog_product_option_type_value'):
                $tableName = 'mageworx_optiontemplates_group_option_type_value';
                break;
            case parent::getTable('catalog_product_option_type_title'):
                $tableName = 'mageworx_optiontemplates_group_option_type_title';
                break;
            case parent::getTable('catalog_product_option_type_price'):
                $tableName = 'mageworx_optiontemplates_group_option_type_price';
                break;
            default:
                $tableName = $origTableName;
        }

        return parent::getTable($tableName);
    }
}
