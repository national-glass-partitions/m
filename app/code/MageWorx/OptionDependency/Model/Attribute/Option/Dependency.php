<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionDependency\Model\Attribute\Option;

use MageWorx\OptionDependency\Model\Attribute\Dependency as DefaultDependency;
use MageWorx\OptionDependency\Model\Config;

class Dependency extends DefaultDependency
{
    protected string $currentProductSku = '';

    /**
     * {@inheritdoc}
     */
    public function collectData($entity, array $options)
    {
        $this->entity  = $entity;
        $this->options = $options;
    }

    /**
     * Delete old dependencies
     *
     */
    public function deleteOldData(array $data)
    {
        $optionIds = [];
        foreach ($data as $dataItem) {
            if (!isset($dataItem['option_id'])) {
                continue;
            }

            $optionIds[] = $dataItem['option_id'];
        }
        if (!$optionIds) {
            return;
        }
        $tableName  = $this->resource->getTableName('mageworx_option_dependency');
        $conditions = 'dp_child_option_id IN (' . implode(',', $optionIds) . ')';
        $this->resource->getConnection()->delete($tableName, $conditions);
        $conditions = 'dp_parent_option_id IN (' . implode(',', $optionIds) . ')';
        $this->resource->getConnection()->delete($tableName, $conditions);
    }

    /**
     * Collect system data (customer group ids, store ids) from Magento 1 product csv
     *
     * @param array $systemData
     * @param array $productData
     * @param array $optionData
     * @param array $valueData
     */
    public function collectOptionsSystemDataMageOne(&$systemData, $productData, $optionData, $valueData = [])
    {
        if ($this->currentProductSku === $productData['sku']) {
            return;
        }

        $this->currentProductSku = $productData['sku'];

        if (empty($productData['options']) || !is_array($productData['options'])) {
            return;
        }

        foreach ($productData['options'] as $optionDatum) {
            if (empty($optionDatum['values']) || !is_array($optionDatum['values'])) {
                continue;
            }

            foreach ($optionDatum['values'] as $valueDatum) {
                if (empty($valueDatum['_custom_option_row_dependent_ids'])) {
                    continue;
                }
                $childDependencyIds = explode(',', $valueDatum['_custom_option_row_dependent_ids']);
                foreach ($childDependencyIds as $childDependencyId) {
                    $systemData['dependencies'][$productData['sku']][]      = [
                        'dp_parent_option_id'      => $optionDatum['_custom_option_in_group_id'],
                        'dp_parent_option_type_id' => $valueDatum['_custom_option_row_in_group_id'],
                        'dp_child_option_id'       => '',
                        'dp_child_option_type_id'  => '',
                        'in_group_id'              => $childDependencyId
                    ];
                    $systemData['usedDependencyIds'][$productData['sku']][] = $childDependencyId;
                }
            }
        }
    }

    /**
     * Prepare data from Magento 1 product csv for future import
     *
     * @param array $systemData
     * @param array $productData
     * @param array $optionData
     * @param array $preparedOptionData
     * @param array $valueData
     * @param array $preparedValueData
     * @return void
     */
    public function prepareOptionsMageOne(
        $systemData,
        $productData,
        $optionData,
        &$preparedOptionData,
        $valueData = [],
        &$preparedValueData = []
    ) {
        if (empty($systemData['dependencies']) || empty($systemData['dependencies'][$productData['sku']])) {
            return;
        }

        $dependencies = [];
        foreach ($systemData['dependencies'][$productData['sku']] as $key => $dependency) {
            if ($dependency['in_group_id'] != $optionData['_custom_option_in_group_id']) {
                continue;
            }
            $dependencies[] = [
                (int)$dependency['dp_parent_option_id'],
                (int)$dependency['dp_parent_option_type_id']
            ];
        }
        if ($dependencies) {
            $preparedOptionData[static::getName()] = $this->baseHelper->jsonEncode($dependencies);
        }
    }

    /**
     * Collect data for magento2 product export
     *
     * @param array $row
     * @param array $data
     * @return void
     */
    public function collectExportDataMageTwo(&$row, $data)
    {
        $prefix        = 'custom_option_';
        $attributeData = null;
        if (!empty($data[$this->getName()])) {
            $attributeData = $this->baseHelper->jsonDecode($data[$this->getName()]);
        }
        if (empty($attributeData) || !is_array($attributeData)) {
            $row[$prefix . $this->getName()] = null;

            return;
        }
        $result = [];
        foreach ($attributeData as $datum) {
            $result[] = implode(',', $datum);
        }
        $row[$prefix . $this->getName()] = $result ? implode('|', $result) : null;
    }

    /**
     * Collect data for magento2 product import
     *
     * @param array $data
     * @return array|null
     */
    public function collectImportDataMageTwo($data)
    {
        if (!$this->hasOwnTable()) {
            return null;
        }

        if (!isset($data['custom_option_' . $this->getName()])) {
            return null;
        }

        $this->entity = $this->dataObjectFactory->create();
        $this->entity->setType('product');
        $dataObject = $this->dataObjectFactory->create();
        $dataObject->setData('is_after_template_save', false);
        $this->entity->setDataObject($dataObject);

        $preparedData = [];

        $attributeData = $data['custom_option_' . $this->getName()];
        if (empty($attributeData)) {
            return $this->collectDependencies();
        }

        $step1 = explode('|', $attributeData);
        foreach ($step1 as $step1Item) {
            $step2          = explode(',', $step1Item);
            $preparedData[] = [
                0 => $step2[0],
                1 => $step2[1]
            ];
        }

        return $this->collectDependenciesImportMageTwo($data, $preparedData);
    }

    /**
     * Collect dependencies for magento2 product import
     *
     * @param array $row
     * @param array $preparedData
     * @return array
     */
    protected function collectDependenciesImportMageTwo($row, $preparedData)
    {
        $data             = [];
        $data['delete'][] = [
            Config::COLUMN_NAME_PRODUCT_ID => $row['product_id'],
        ];

        foreach ($preparedData as $preparedDatum) {
            $data['save'][] = [
                Config::COLUMN_NAME_DP_CHILD_OPTION_ID       => $row['custom_option_id'],
                Config::COLUMN_NAME_DP_CHILD_OPTION_TYPE_ID  => '',
                Config::COLUMN_NAME_DP_PARENT_OPTION_ID      => $preparedDatum[0],
                Config::COLUMN_NAME_DP_PARENT_OPTION_TYPE_ID => $preparedDatum[1],
                Config::COLUMN_NAME_PRODUCT_ID               => $row['product_id'],
                Config::COLUMN_NAME_IS_PROCESSED_DP_COLUMNS  => '1'
            ];
        }

        return $data;
    }
}
