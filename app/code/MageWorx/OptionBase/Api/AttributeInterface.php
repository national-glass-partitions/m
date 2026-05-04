<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionBase\Api;

use Magento\Catalog\Api\Data\ProductInterface;

interface AttributeInterface
{
    /**
     * Get attribute name
     */
    public function getName();

    /**
     * Check if attribute has own table in database
     */
    public function hasOwnTable();

    /**
     * Get table name, used when attribute use individual tables
     *
     * @param string $type
     */
    public function getTableName($type = '');

    /**
     * Collect attribute data
     * @param \MageWorx\OptionBase\Model\Entity\Group|\MageWorx\OptionBase\Model\Entity\Product $entity
     * @param array $options
     */
    public function collectData($entity, array $options);

    /**
     * Delete old attribute data
     *
     * @param array $data
     */
    public function deleteOldData(array $data);

    /**
     * Prepare attribute data for frontend js config
     * @param \Magento\Catalog\Model\Product\Option|\Magento\Catalog\Model\Product\Option\Value|array $data
     */
    public function prepareDataForFrontend($data);

    /**
     * Prepare attribute data before save
     * @param \Magento\Catalog\Model\Product\Option|\Magento\Catalog\Model\Product\Option\Value|array $data
     */
    public function prepareDataBeforeSave($data);

    /**
     * Process attribute in case of product/group duplication
     *
     * @param string $newId
     * @param string $oldId
     * @param string $entityType
     */
    public function processDuplicate($newId, $oldId, $entityType = 'product');

    /**
     * Load attribute data from DB for specified entities
     * @param ProductInterface $product
     * @param int[] $ids
     * @return array - array of loaded data by ids
     */
    public function loadAttributeData(ProductInterface $product, array $ids): array;
}
