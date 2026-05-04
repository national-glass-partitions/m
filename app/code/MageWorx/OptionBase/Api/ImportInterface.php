<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionBase\Api;

use Magento\Framework\Exception\LocalizedException;

interface ImportInterface
{
    /**
     * Validate Magento 1 template
     *
     * @param array $optionData
     * @throws LocalizedException
     * @return void
     */
    public function validateTemplateMageOne($optionData);

    /**
     * Collect system data (customer group ids, store ids) from Magento 1 template data
     *
     * @param array $data
     * @return array
     */
    public function collectTemplateSystemDataMageOne($data);

    /**
     * Prepare data from Magento 1 template for future import
     *
     * @param array $groupData
     * @param array $optionData
     * @param array $valueData
     * @return void
     */
    public function prepareTemplateMageOne(&$groupData, &$optionData, &$valueData);

    /**
     * Collect Magento 1 template data
     *
     * @param array $optionData
     * @return void
     */
    public function importTemplateMageOne($optionData);

    /**
     * Validate Magento 2 template
     *
     * @param array $optionData
     * @throws LocalizedException
     */
    public function validateTemplateMageTwo($optionData);

    /**
     * Collect system data (customer group ids, store ids) from Magento 2 template data
     *
     * @param array $data
     * @return array
     */
    public function collectTemplateSystemDataMageTwo($data);

    /**
     * Prepare data from Magento 2 template for future import
     *
     * @param array $groupData
     * @param array $optionData
     * @param array $valueData
     * @return void
     */
    public function prepareTemplateMageTwo(&$groupData, &$optionData, &$valueData);

    /**
     * Import Magento 2 template data
     *
     * @param array $optionData
     * @return void
     */
    public function importTemplateMageTwo($optionData);

    /**
     * Validate Magento 1 product csv
     *
     * @param array $optionData
     * @return void
     */
    public function validateOptionsMageOne($optionData);

    /**
     * Collect system data (customer group ids, store ids) from Magento 1 product csv
     *
     * @param array $systemData
     * @param array $productData
     * @param array $optionData
     * @param array $valueData
     * @throws LocalizedException
     * @return void
     */
    public function collectOptionsSystemDataMageOne(&$systemData, $productData, $optionData, $valueData =[]);

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
    public function prepareOptionsMageOne($systemData, $productData, $optionData, &$preparedOptionData, $valueData = [], &$preparedValueData = []);

    /**
     * Import Magento 1 product csv data
     *
     * @param array $optionData
     * @return void
     */
    public function importOptionsMageOne($optionData);

    /**
     * Collect data for Magento2 product export
     *
     * @param array $row
     * @param array $data
     * @return void
     */
    public function collectExportDataMageTwo(&$row, $data);

    /**
     * Prepare data for attributes, which do NOT have own database tables, for Magento2 product import
     *
     * @param array $data
     * @param string $type
     * @return mixed
     */
    public function prepareImportDataMageTwo($data, $type);

    /**
     * Collect data for attributes, which have own database tables, for Magento2 product import
     *
     * @param array $data
     * @return array|null
     */
    public function collectImportDataMageTwo($data);
}
