<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionBase\Model\Product\Option;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use MageWorx\OptionBase\Api\AttributeInterface;
use MageWorx\OptionBase\Api\ImportInterface;
use MageWorx\OptionBase\Helper\Data as BaseHelper;

abstract class AbstractAttribute implements AttributeInterface, ImportInterface
{
    protected ResourceConnection $resource;
    protected BaseHelper $baseHelper;
    protected DataObjectFactory $dataObjectFactory;
    protected $entity;

    public function __construct(
        ResourceConnection $resource,
        BaseHelper $baseHelper,
        DataObjectFactory $dataObjectFactory
    ) {
        $this->resource          = $resource;
        $this->baseHelper        = $baseHelper;
        $this->dataObjectFactory = $dataObjectFactory;
    }

    /**
     * Get attribute name
     *
     * @return string
     */
    public function getName()
    {
        return '';
    }

    /**
     * Check if attribute has own table in database
     *
     * @return bool
     */
    public function hasOwnTable()
    {
        return false;
    }

    /**
     * Get table name
     * Used when attribute has individual table
     *
     * @param string $type
     * @return string
     */
    public function getTableName($type = '')
    {
        return '';
    }

    /**
     * Collect attribute data
     *
     * @param \MageWorx\OptionBase\Model\Entity\Group|\MageWorx\OptionBase\Model\Entity\Product $entity
     * @param array $options
     * @return void
     */
    public function collectData($entity, array $options)
    {
        $this->entity = $entity;

        return;
    }

    /**
     * Delete old attribute data
     *
     * @param array $data
     * @return void
     */
    public function deleteOldData(array $data)
    {
        return;
    }

    /**
     * Prepare attribute data before save
     * Returns modified value, which is ready for db save
     *
     * @param \Magento\Catalog\Model\Product\Option|\Magento\Catalog\Model\Product\Option\Value|array $data
     * @return string
     */
    public function prepareDataBeforeSave($data)
    {
        if (is_object($data)) {
            return $data->getData($this->getName());
        } elseif (is_array($data) && isset($data[$this->getName()])) {
            return $data[$this->getName()];
        }

        return '';
    }

    /**
     * Prepare attribute data for frontend js config
     *
     * @param \Magento\Catalog\Model\Product\Option|\Magento\Catalog\Model\Product\Option\Value $object
     * @return array
     */
    public function prepareDataForFrontend($object)
    {
        return [$this->getName() => $object->getData($this->getName())];
    }

    /**
     * Process attribute in case of product/group duplication
     *
     * @param string $newId
     * @param string $oldId
     * @param string $entityType
     */
    public function processDuplicate($newId, $oldId, $entityType = 'product')
    {
        return;
    }

    /**
     * Validate Magento 1 template import
     *
     * @param array $groupData
     * @throws \Exception
     * @throws LocalizedException
     */
    public function validateTemplateMageOne($groupData)
    {
        return;
    }

    /**
     * Collect system data (customer group ids, store ids) from Magento 1 template data
     *
     * @param array $optionData
     */
    public function collectTemplateSystemDataMageOne($optionData)
    {
        return;
    }

    /**
     * Prepare data from Magento 1 template for future import
     *
     * @param array $groupData
     * @param array $optionData
     * @param array $valueData
     */
    public function prepareTemplateMageOne(&$groupData, &$optionData, &$valueData)
    {
        return;
    }

    /**
     * Import Magento 1 template data
     *
     * @param array $data
     * @return int|string|
     */
    public function importTemplateMageOne($data)
    {
        return $data[$this->getName()] ?? 0;
    }

    /**
     * Validate Magento 2 template
     *
     * @param array $groupData
     * @throws \Exception
     * @throws LocalizedException
     */
    public function validateTemplateMageTwo($groupData)
    {
        return;
    }

    /**
     * Collect system data (customer group ids, store ids) from Magento 2 template data
     *
     * @param array $optionData
     */
    public function collectTemplateSystemDataMageTwo($optionData)
    {
        return;
    }

    /**
     * Prepare data from Magento 2 template for future import
     *
     * @param array $groupData
     * @param array $optionData
     * @param array $valueData
     */
    public function prepareTemplateMageTwo(&$groupData, &$optionData, &$valueData)
    {
        return;
    }

    /**
     * Import Magento 2 template data
     *
     * @param array $data
     * @return int|string|null
     */
    public function importTemplateMageTwo($data)
    {
        return $data[$this->getName()] ?? 0;
    }

    /**
     * Validate Magento 1 product csv
     *
     * @param array $groupData
     * @throws \Exception
     * @throws LocalizedException
     */
    public function validateOptionsMageOne($groupData)
    {
        return;
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
        return;
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
        return;
    }

    /**
     * Import Magento 1 product csv data
     *
     * @param array $data
     * @return int|string|
     */
    public function importOptionsMageOne($data)
    {
        return $data[$this->getName()] ?? 0;
    }

    /**
     * Collect system stores from data by key
     *
     * @param array $data
     * @param string $key
     * @return array
     */
    protected function collectStoresDataByKey($data, $key)
    {
        $storeIdMap = [];
        if (empty($data[$key]) || !is_string($data[$key])) {
            return $storeIdMap;
        }

        $decodedData = $this->baseHelper->jsonDecode($data[$key]);
        if (!is_array($decodedData)) {
            return $storeIdMap;
        }

        foreach ($decodedData as $decodedDatum) {
            if (!empty($decodedDatum['store_id'])) {
                $storeIdMap[$decodedDatum['store_id']] = $decodedDatum['store_id'];
            } elseif (!empty($decodedDatum['customer_store_id'])) {
                $storeIdMap[$decodedDatum['customer_store_id']] = $decodedDatum['customer_store_id'];
            }
        }

        return $storeIdMap;
    }

    /**
     * Collect system customer group from data by key
     *
     * @param array $data
     * @param string $key
     * @return array
     */
    protected function collectCustomerGroupsDataByKey($data, $key)
    {
        $customerGroupMap = [];
        if (empty($data[$key]) || !is_string($data[$key])) {
            return $customerGroupMap;
        }

        $decodedData = $this->baseHelper->jsonDecode($data[$key]);
        if (!is_array($decodedData)) {
            return $customerGroupMap;
        }
        foreach ($decodedData as $decodedDatum) {
            if (!isset($decodedDatum['customer_group_id']) ||
                $this->baseHelper->isAllCustomerGroupId((string)$decodedDatum['customer_group_id'])
            ) {
                continue;
            }
            $customerGroupMap[$decodedDatum['customer_group_id']] = $decodedDatum['customer_group_id'];
        }

        return $customerGroupMap;
    }

    /**
     * Check if store has equivalent in system data map
     *
     * @param array $systemData
     * @param string $store
     * @return bool
     */
    protected function hasStoreEquivalent($systemData, $store)
    {
        if (isset($systemData['map']['store'][$store]) && $systemData['map']['store'][$store] !== '') {
            return true;
        }

        return false;
    }

    /**
     * Check if customer group has equivalent in system data map
     *
     * @param array $systemData
     * @param string $customerGroup
     * @return bool
     */
    protected function hasCustomerGroupEquivalent($systemData, $customerGroup)
    {
        if (isset($systemData['map']['customer_group'][$customerGroup])
            && $systemData['map']['customer_group'][$customerGroup] !== ''
        ) {
            return true;
        }

        return false;
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
        $prefix                          = isset($data['option_type_id']) ? 'custom_option_row_' : 'custom_option_';
        $row[$prefix . $this->getName()] = $data[$this->getName()] ?? null;
    }

    /**
     * Collect data for attributes, which have own database tables, for Magento2 product import
     *
     * @param array $data
     * @return array|null
     */
    public function collectImportDataMageTwo($data)
    {
        if (!$this->hasOwnTable()) {
            return null;
        }

        return [];
    }

    /**
     * Prepare data for attributes, which do NOT have own database tables, for Magento2 product import
     *
     * @param array $data
     * @param string $type
     * @return mixed
     */
    public function prepareImportDataMageTwo($data, $type)
    {
        if ($type === 'value') {
            return $data['custom_option_row_' . $this->getName()] ?? null;
        }

        return $data['custom_option_' . $this->getName()] ?? null;
    }

    /**
     * Encode symbols for correct M2 product export
     *
     * @param string $data
     * @return string
     */
    public function encodeSymbols($data): string
    {
        $data = str_replace(',', '&comma', $data);
        $data = str_replace('|', '&separator', $data);

        return str_replace('=', '&equal', $data);
    }

    /**
     * Decode symbols for correct M2 product import
     *
     * @param string $data
     * @return string
     */
    public function decodeSymbols($data): string
    {
        $data = str_replace('&comma', ',', $data);
        $data = str_replace('&separator', '|', $data);

        return str_replace('&equal', '=', $data);
    }

    /**
     * Load attribute data from DB for specified entities
     * @param ProductInterface $product
     * @param int[] $ids
     * @return array - array of loaded data by ids
     */
    public function loadAttributeData(ProductInterface $product, array $ids): array
    {
        return []; // Return nothing if not implemented
    }
}
