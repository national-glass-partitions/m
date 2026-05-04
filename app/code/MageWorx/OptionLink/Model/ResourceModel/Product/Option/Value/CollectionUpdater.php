<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionLink\Model\ResourceModel\Product\Option\Value;

use \MageWorx\OptionLink\Helper\Attribute as HelperAttribute;
use \Magento\Framework\ObjectManagerInterface as ObjectManager;
use \Magento\Catalog\Model\ResourceModel\Product\Option\Value\Collection;

/**
 * Class CollectionUpdater. Used for update sql of original Option Value Collection.
 */
class CollectionUpdater
{
    const KEY_TABLE_OPTIONLINK_PRODUCT = 'optionlink_product';

    const KEY_FIELD_SKU_IS_VALID = 'sku_is_valid';

    protected HelperAttribute $helperAttribute;
    protected ?ObjectManager $objectManager = null;

    /**
     * CollectionUpdater constructor.
     *
     * @param HelperAttribute $helperAttribute
     * @param ObjectManager $objectManager
     */
    public function __construct(
        HelperAttribute $helperAttribute,
        ObjectManager $objectManager
    ) {
        $this->helperAttribute = $helperAttribute;
        $this->objectManager   = $objectManager;
    }

    /**
     * Join Product table with replaced attributes.
     *
     * @param Collection $collection
     * @return $this
     */
    public function joinProductTable(Collection $collection)
    {
        $productTable = $this->createProductTable();

        $collection->getSelect()->joinLeft(
            [self::KEY_TABLE_OPTIONLINK_PRODUCT => $productTable],
            self::KEY_TABLE_OPTIONLINK_PRODUCT .'.sku = main_table.sku'
        );

        return $this;
    }

    /**
     * This method checks whether it is necessary to add a field to a request.
     * Checks the title and price fields only, for others 'true' returns.
     *
     * @param Collection $collection
     * @param string $fieldName
     * @return bool
     */
    public function canAddField(Collection $collection, $fieldName)
    {
        if (!$this->helperAttribute->isAttributeExist($fieldName)) {
            return false;
        }
        $checkFields = ['title', 'price'];

        if (!in_array($fieldName, $checkFields)) {
            return true;
        }

        $from = $collection->getSelect()->getPart('from');

        $coincidence = 0;
        foreach ($from as $tableAlias => $table) {
            if ($tableAlias == 'default_value_' . $fieldName ||
                $tableAlias == 'store_value_' . $fieldName) {
                $coincidence++;
            }
        }

        if ($coincidence == 2) {
            return true;
        }

        return false;
    }

    /**
     * Reset custom columns in sql from "Select" part.
     * Later we will paste it back with changes.
     *
     * @param Collection $collection
     * @param array $fields
     * @return $this
     */
    public function resetColumns(Collection $collection, $fields)
    {
        $columns = $collection->getSelect()->getPart('columns');

        foreach ($columns as $key => $column) {
            if ($column[1] == '*') {
                unset($columns[$key]);
                continue;
            }

            foreach ($fields as $field) {
                if ($column[2] == $field) {
                    unset($columns[$key]);
                    continue;
                }
            }
        }

        $collection->getSelect()->setPart('columns', $columns);

        return $this;
    }

    /**
     * Add original fields (not modified)
     * from main_table (catalog_product_option_type_value) table.
     *
     * @param Collection $collection
     * @param $fields
     * @return $this
     */
    public function addOriginalColumns(Collection $collection, $fields)
    {
        // get all columns from catalog_product_option_type_value table
        $describe = array_keys(
            $collection->getConnection()->describeTable(
                $collection->getMainTable()
            )
        );

        // get diff between original columns and selected fields in module setting
        $describe = array_diff($describe, $fields);

        // set diff columns, custom columns will be set later
        $collection->getSelect()
                   ->columns($describe);

        return $this;
    }

    /**
     * Generate sql for create Product table with attributes.
     *
     * @return \Zend_Db_Expr
     */
    protected function createProductTable()
    {
        $collection = $this->objectManager
            ->create('Magento\Catalog\Model\ResourceModel\Product\CollectionFactory')
            ->create();

        $this->joinProductAttributes($collection);

        return new \Zend_Db_Expr('(' . $collection->getSelect()->assemble() . ')');
    }

    /**
     * Join attributes selected in setting to product table.
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function joinProductAttributes(
        \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
    ): CollectionUpdater {
        $attributes = $this->helperAttribute->getFieldsMap();

        foreach ($attributes as $name => $attribute) {
            if ($attribute['type'] == 'field') {
                $collection
                    ->joinField(
                        $attribute['alias'],
                        $attribute['table'],
                        $attribute['field'],
                        $attribute['bind'],
                        $attribute['cond'],
                        $attribute['joinType']
                    );
            } elseif ($attribute['type'] == 'custom' && $attribute['joinType'] == 'left') {
                $collection->getSelect()->joinLeft(
                    $attribute['table'],
                    $attribute['cond'],
                    $attribute['cols']
                );
            } else {
                if ($this->helperAttribute->isAttributeExist($name)) {
                    $collection
                        ->addAttributeToSelect(
                            $name,
                            $attribute['joinType']
                        );
                }
            }
        }

        return $this;
    }
}
