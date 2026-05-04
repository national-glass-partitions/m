<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionDependency\Model;

use \Magento\Framework\App\ResourceConnection;

class Converter
{
    const CONVERTING_ENTITY_PRODUCT  = 'product';
    const CONVERTING_ENTITY_GROUP    = 'template';
    const CONVERTING_MODE_MAGENTO    = 'magento';
    const CONVERTING_MODE_MAGEWORX   = 'mageworx';

    protected array $mapTable = [
        'product' => [
            'option' => 'catalog_product_option',
            'value' => 'catalog_product_option_type_value',
        ],
        'template' => [
            'option' => 'mageworx_optiontemplates_group_option',
            'value' => 'mageworx_optiontemplates_group_option_type_value',
        ],
    ];

    protected array $mapType = [
        'option' => 'option_id',
        'value' => 'option_type_id'
    ];

    protected array $templateFieldMap = [
        'option' => 'option_id',
        'value' => 'option_value_id'
    ];

    protected array $data = [];

    protected int $productId;

    protected string $convertTo = self::CONVERTING_MODE_MAGENTO;

    protected string $convertWhere = self::CONVERTING_ENTITY_PRODUCT;

    protected ResourceConnection $resource;

    public function __construct(
        ResourceConnection $resource
    ) {

        $this->resource = $resource;
    }

    /**
     * Set data
     *
     * @param array $data
     * @return this
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Set product id
     *
     * @param integer $productId
     * @return this
     */
    public function setProductId($productId)
    {
        $this->productId = $productId;
        return $this;
    }

    /**
     * Set convert where
     * Possible values: product, template
     *
     * @param string $convertWhere
     * @return this
     */
    public function setConvertWhere($convertWhere)
    {
        $this->convertWhere = $convertWhere;
        return $this;
    }

    /**
     * Set convert to
     * Possible values: magento, mageworx
     *
     * @param string $convertTo
     * @return this
     */
    public function setConvertTo($convertTo)
    {
        $this->convertTo = $convertTo;
        return $this;
    }

    /**
     * Convert magento/mageworx IDs to mageworx/magento IDs, used to apply template on product
     *
     * @return array|void
     */
    public function convert()
    {
        if (!$this->canConvert()) {
            return;
        }

        if ($this->convertTo == self::CONVERTING_MODE_MAGEWORX) {
            // prepare magento option|value ids
            $magentoOptionIds = $this->prepareIds($this->data, 'option');
            $magentoValueIds = $this->prepareIds($this->data, 'value');

            // load ids same to magento ids
            $optionIds = $this->loadIds($magentoOptionIds, 'option');
            $valueIds = $this->loadIds($magentoValueIds, 'value');

            $this->replaceData($optionIds, $valueIds);
        }

        return $this->data;
    }

    /**
     * Check if there some data to convert
     *
     * @return bool
     */
    protected function canConvert()
    {
        if (empty($this->data)) {
            return false;
        }

        return true;
    }

    /**
     * Form array from ID pairs
     * @param array $array
     * @param string $type
     * @return array
     */
    public function prepareIds($array, $type = 'option')
    {
        $result = [];
        if ($type == 'option') {
            $index = 0;
        } else {
            $index = 1;
        }

        foreach ($array as $row) {
            $parentId = $row[$index];

            if (!in_array($parentId, $result)) {
                $result[] = $parentId;
            }
        }

        return $result;
    }

    /**
     * Load IDs
     * @param array $magentoIds
     * @param string $type
     * @return array
     */
    protected function loadIds($magentoIds, $type = 'option')
    {
        $connection = $this->resource->getConnection();

        $table = $this->resource->getTableName($this->mapTable[$this->convertWhere][$type]);
        $field1 = $this->mapType[$type];
        $field2 = $this->templateFieldMap[$type];

        if ($type == 'option') {
            $select = $connection
                ->select()
                ->from($table, ['group_' . $field2, $field1])
                ->where('product_id = ' . $this->productId .
                        ' AND group_' . $field2 . ' IN (' . implode(",", $magentoIds) . ')');
        } elseif ($type == 'value') {
            $select = $connection
                ->select()
                ->from($table . ' AS main_table', ['main_table.group_' . $field2, 'main_table.' . $field1])
                ->joinLeft($this->resource->getTableName('catalog_product_option') . ' AS cpo',
                           "cpo.option_id = main_table.option_id"
                )
                ->where('cpo.product_id = ' . $this->productId .
                        ' AND main_table.group_' . $field2 . ' IN (' . implode(",", $magentoIds) . ')');
        }

        return $connection->fetchPairs($select);
    }

    /**
     * Replace data using third-party arrays
     * @param array $array1
     * @param array $array2
     * @return array
     */
    protected function replaceData($array1, $array2)
    {
        $data = $this->data;

        foreach ($data as $rowKey => $row) {
            foreach ($row as $key => $id) {
                if ($key == 0) {
                    if (isset($array1[$id])) {
                        $this->data[$rowKey][$key] = $array1[$id];
                    }
                } elseif ($key == 1) {
                    if (isset($array2[$id])) {
                        $this->data[$rowKey][$key] = $array2[$id];
                    }
                }
            }
        }
    }
}
