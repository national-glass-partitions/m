<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionDependency\Model\Attribute\OptionValue;

use MageWorx\OptionDependency\Model\Attribute\Dependency as DefaultDependency;
use MageWorx\OptionDependency\Model\Config;

class Dependency extends DefaultDependency
{
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
            if ($dependency['in_group_id'] != $valueData['_custom_option_row_in_group_id']) {
                continue;
            }
            $dependencies[] = [
                (int)$dependency['dp_parent_option_id'],
                (int)$dependency['dp_parent_option_type_id']
            ];
        }

        if ($dependencies) {
            $preparedValueData[static::getName()] = $this->baseHelper->jsonEncode($dependencies);
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
        $prefix        = 'custom_option_row_';
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

        if (!isset($data['custom_option_row_' . $this->getName()])) {
            return null;
        }

        $this->entity = $this->dataObjectFactory->create();
        $this->entity->setType('product');
        $dataObject = $this->dataObjectFactory->create();
        $dataObject->setData('is_after_template_save', false);
        $this->entity->setDataObject($dataObject);

        $preparedData = [];

        $attributeData = $data['custom_option_row_' . $this->getName()];
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
        $data = [];

        foreach ($preparedData as $preparedDatum) {
            $data['save'][]   = [
                Config::COLUMN_NAME_DP_CHILD_OPTION_ID       => $row['custom_option_id'],
                Config::COLUMN_NAME_DP_CHILD_OPTION_TYPE_ID  => $row['custom_option_row_id'],
                Config::COLUMN_NAME_DP_PARENT_OPTION_ID      => $preparedDatum[0],
                Config::COLUMN_NAME_DP_PARENT_OPTION_TYPE_ID => $preparedDatum[1],
                Config::COLUMN_NAME_PRODUCT_ID                => $row['product_id'],
                Config::COLUMN_NAME_IS_PROCESSED_DP_COLUMNS  => '1'
            ];
            $data['delete'][] = [
                Config::COLUMN_NAME_DP_PARENT_OPTION_ID => $preparedDatum[0],
            ];
        }

        return $data;
    }
}
