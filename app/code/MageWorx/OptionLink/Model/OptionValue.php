<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionLink\Model;

use \MageWorx\OptionTemplates\Model\ResourceModel\Group\Option\Value\CollectionFactory as MageWorxValueFactory;
use \Magento\Catalog\Model\ResourceModel\Product\Option\Value\CollectionFactory as MagentoValueFactory;
use \Magento\Store\Model\StoreManagerInterface as StoreManager;
use \Magento\Framework\ObjectManagerInterface as ObjectManager;

/**
 * Class OptionValue.
 */
class OptionValue
{
    protected MageWorxValueFactory $mageworxValueFactory;
    protected MagentoValueFactory $magentoValueFactory;
    protected StoreManager $storeManager;
    protected ?ObjectManager $objectManager = null;

    /**
     * OptionValue constructor.
     *
     * @param StoreManager $storeManager
     * @param ObjectManager $objectManager
     */
    public function __construct(
        MageWorxValueFactory $mageworxValueFactory,
        MagentoValueFactory $magentoValueFactory,
        StoreManager $storeManager,
        ObjectManager $objectManager
    ) {
        $this->mageworxValueFactory = $mageworxValueFactory;
        $this->magentoValueFactory = $magentoValueFactory;
        $this->storeManager = $storeManager;
        $this->objectManager = $objectManager;
    }

    /**
     * Update option values to original data before option save.
     *
     * @param array $currentValues
     * @param array $originalValues
     * @param array $fields
     * @return array
     */
    public function updateOptionValuesBeforeSave($currentValues, $originalValues, $fields)
    {
        foreach ($currentValues as $vKey => $currentValue) {
            $id = isset($currentValue['option_type_id']) ? $currentValue['option_type_id'] : $vKey;
            if (!isset($originalValues[$id])) {
                continue;
            }

            $originalValue = $originalValues[$id];

            if (!isset($currentValue['sku'])) {
                continue;
            }

            if (!isset($currentValue['sku_is_valid'])) {
                continue;
            }

            if (($currentValue['sku'] || !$currentValue['sku']) && $currentValue['sku_is_valid']) {
                foreach ($fields as $field) {
                    $currentValues[$vKey][$field] = $originalValue[$field];
                }
            }
        }

        return $currentValues;
    }

    /**
     * Retrieve original option value data by options ids
     *
     * @param bool $isProductSave
     * @param int|array $optionIds
     * @return array
     */
    public function loadOriginalOptions($optionIds, $isProductSave = true)
    {
        $options = [];
        $storeId = $this->storeManager->getStore()->getId();

        if ($isProductSave) { // we save product
            $valuesCollection = $this->magentoValueFactory->create();
        } else { // we save options template
            $valuesCollection = $this->mageworxValueFactory->create();
        }

        $valuesCollection
            ->addTitleToResult($storeId)
            ->addPriceToResult($storeId);

        $valuesCollection->getSelect()
            ->where('option_id IN (?)', $optionIds);

        $sql = $valuesCollection->getSelect();

        $resource = $this->objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();

        $options = $connection->fetchAssoc($sql);

        return $options;
    }
}
