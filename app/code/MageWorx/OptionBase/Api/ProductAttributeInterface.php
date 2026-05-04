<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionBase\Api;

interface ProductAttributeInterface
{
    /**
     * Get name of attribute
     *
     * @return string
     */
    public function getName();

    /**
     * Get table name, used when attribute use individual tables
     *
     * @return string
     */
    public function getTableName();

    /**
     * Collect product attribute data
     *
     * @param \MageWorx\OptionBase\Model\Entity\Group|\MageWorx\OptionBase\Model\Entity\Product $entity
     * @return void
     */
    public function collectData($entity);

    /**
     * Delete old product attribute data
     *
     * @param $data
     * @return void
     */
    public function deleteOldData(array $data);

    /**
     * Get priority value of attribute
     *
     * @return null|int|string
     */
    public function getPriorityValue();

    /**
     * Get default value of attribute
     *
     * @return int|string
     */
    public function getDefaultValue();

    /**
     * Validate Magento 1 template import
     *
     * @param array $groupData
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return void
     */
    public function validateTemplateImportMageOne($groupData);

    /**
     * Validate Magento 2 template import
     *
     * @param array $groupData
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return void
     */
    public function validateTemplateImportMageTwo($groupData);

    /**
     * Import Magento 1 template data
     *
     * @param array $groupData
     * @return array
     */
    public function importTemplateMageOne($groupData);

    /**
     * Import Magento 2 template data
     *
     * @param array $groupData
     * @return array
     */
    public function importTemplateMageTwo($groupData);

    /**
     * Prepare Magento 1 product attributes for import
     *
     * @param array $productAttributesData
     * @param array $data
     * @return void
     */
    public function prepareOptionsMageOne(&$productAttributesData, $data);

    /**
     * Flag to check if attribute should be skipped during Magento 2 export
     *
     * @return bool
     */
    public function shouldSkipExportMageTwo();

    /**
     * Collect data for Magento2 product import
     *
     * @param array $data
     * @return array|null
     */
    public function collectImportDataMageTwo($data);
}
