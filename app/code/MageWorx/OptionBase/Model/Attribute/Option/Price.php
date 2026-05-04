<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionBase\Model\Attribute\Option;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObjectFactory;
use Magento\Store\Model\Store;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionBase\Model\OptionPrice;
use MageWorx\OptionBase\Model\Product\Option\AbstractAttribute;
use Magento\Framework\Serialize\Serializer\Json as Serializer;

class Price extends AbstractAttribute
{
    const FIELD_MAGE_ONE_OPTIONS_IMPORT = '_custom_option_price';

    protected Serializer $serializer;

    public function __construct(
        ResourceConnection $resource,
        BaseHelper $baseHelper,
        DataObjectFactory $dataObjectFactory,
        Serializer $serializer
    ) {
        $this->serializer = $serializer;
        parent::__construct($resource, $baseHelper, $dataObjectFactory);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return OptionPrice::KEY_MAGEWORX_OPTION_PRICE;
    }

    /**
     * {@inheritdoc}
     *
     * @return boolean
     */
    public function hasOwnTable()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $type
     * @return string
     */
    public function getTableName($type = '')
    {
        $map = [
            'product' => OptionPrice::TABLE_NAME,
            'group'   => OptionPrice::OPTIONTEMPLATES_TABLE_NAME,
        ];
        if (!$type) {
            return $map[$this->entity->getType()];
        }

        return $map[$type];
    }

    /**
     * {@inheritdoc}
     *
     * @param \MageWorx\OptionBase\Model\Entity\Group|\MageWorx\OptionBase\Model\Entity\Product $entity
     * @param array $options
     * @return array
     */
    public function collectData($entity, array $options)
    {
        $this->entity               = $entity;
        $currentStoreId             = (int)$entity->getDataObject()->getData('store_id') ?: 0;
        $isWebsiteCatalogPriceScope = $this->baseHelper->isWebsiteCatalogPriceScope();

        $savedItems = [];
        $items      = [];
        foreach ($options as $option) {
            if (empty($option[$this->getName()])) {
                continue;
            }
            $savedItems[$option[OptionPrice::FIELD_OPTION_ID]] = $option[$this->getName()];

            if (!$isWebsiteCatalogPriceScope && $currentStoreId !== Store::DEFAULT_STORE_ID) {
                continue;
            }
            $items[$option[OptionPrice::FIELD_OPTION_ID]] = [
                OptionPrice::FIELD_PRICE      => $option[OptionPrice::FIELD_PRICE],
                OptionPrice::FIELD_PRICE_TYPE => $option[OptionPrice::FIELD_PRICE_TYPE],
                OptionPrice::FIELD_STORE_ID   => $currentStoreId
            ];
        }

        return $this->collectPrices($items, $savedItems);
    }

    /**
     * Collect option value prices
     *
     * @param array $items
     * @param array $savedItems
     * @return array
     */
    protected function collectPrices($items, $savedItems)
    {
        $data = [];

        foreach ($savedItems as $savedItemKey => $savedItemValue) {
            $decodedJsonData = $savedItemValue ? $this->serializer->unserialize($savedItemValue) : null;

            if (empty($decodedJsonData)) {
                continue;
            }
            $data['delete'][] = [
                OptionPrice::FIELD_OPTION_ID => $savedItemKey,
            ];
            $this->mergeNewPrices($decodedJsonData, $items, $savedItemKey);
            foreach ($decodedJsonData as $dataItem) {
                $data['save'][] = [
                    OptionPrice::FIELD_OPTION_ID  => $savedItemKey,
                    OptionPrice::FIELD_STORE_ID   => $dataItem[OptionPrice::FIELD_STORE_ID],
                    OptionPrice::FIELD_PRICE      => $dataItem[OptionPrice::FIELD_PRICE],
                    OptionPrice::FIELD_PRICE_TYPE => $dataItem[OptionPrice::FIELD_PRICE_TYPE]
                ];
            }
        }

        return $data;
    }

    /**
     * Merge new prices with the old ones
     * Prepare data before save to db, used because we re-insert all prices for all store views
     *
     * @param array $decodedJsonData
     * @param array $items
     * @param int $savedItemKey
     */
    protected function mergeNewPrices(&$decodedJsonData, $items, $savedItemKey)
    {
        foreach ($items as $itemKey => $itemData) {
            if ($itemKey != $savedItemKey) {
                continue;
            }
            $storeId        = $itemData[OptionPrice::FIELD_STORE_ID];
            $storePrice     = $itemData[OptionPrice::FIELD_PRICE];
            $storePriceType = $itemData[OptionPrice::FIELD_PRICE_TYPE];
            if ($storePrice === '') {
                if (is_array($decodedJsonData) && isset($decodedJsonData[$storeId])) {
                    unset($decodedJsonData[$storeId]);
                }
                continue;
            }
            $isSaved = false;
            foreach ($decodedJsonData as $dataKey => $dataItem) {
                if ($dataItem[OptionPrice::FIELD_STORE_ID] == $storeId) {
                    $decodedJsonData[$dataKey][OptionPrice::FIELD_PRICE]      = $storePrice;
                    $decodedJsonData[$dataKey][OptionPrice::FIELD_PRICE_TYPE] = $storePriceType;
                    $isSaved                                                  = true;
                }
            }
            if ($isSaved) {
                continue;
            }
            $decodedJsonData[] = [
                OptionPrice::FIELD_STORE_ID   => $storeId,
                OptionPrice::FIELD_PRICE      => $storePrice,
                OptionPrice::FIELD_PRICE_TYPE => $storePriceType
            ];
        }
    }

    /**
     * Delete old mageworx option value prices
     *
     * @param array $data
     * @return void
     */
    public function deleteOldData(array $data)
    {
        $optionIds = [];
        foreach ($data as $dataItem) {
            $optionIds[] = $dataItem[OptionPrice::FIELD_OPTION_ID];
        }
        if (!$optionIds) {
            return;
        }
        $tableName  = $this->resource->getTableName($this->getTableName());
        $conditions = OptionPrice::FIELD_OPTION_ID . " IN (" . implode(',', $optionIds) . ")";
        $this->resource->getConnection()->delete($tableName, $conditions);
    }

    /**
     * {@inheritdoc}
     */
    public function prepareDataForFrontend($object)
    {
        return [];
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
        if (empty($optionData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT])
            || !is_array($optionData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT])
        ) {
            return;
        }

        foreach ($optionData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT] as $datumStore => $datumValue) {
            $systemData['store'][$datumStore] = $datumStore;
        }
    }

    /**
     * Collect system data (customer group ids, store ids) from Magento 2 template data
     *
     * @param array $data
     * @return array
     */
    public function collectTemplateSystemDataMageTwo($data)
    {
        return $this->collectStoresDataByKey($data, 'mageworx_option_type_price');
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
        if (empty($optionData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT])
            || !is_array($optionData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT])
        ) {
            return;
        }

        $mageworxPrice = [];
        foreach ($optionData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT] as $datumStore => $datumValue) {
            if (!$this->hasStoreEquivalent($systemData, $datumStore)) {
                continue;
            }
            $priceType = substr($datumValue, -1) === '%' ? 'percent' : 'fixed';
            $price     = (float)rtrim($datumValue, '%');

            $mageworxPrice[] = [
                OptionPrice::FIELD_STORE_ID   => $systemData['map']['store'][$datumStore],
                OptionPrice::FIELD_PRICE      => $price,
                OptionPrice::FIELD_PRICE_TYPE => $priceType,
            ];
        }
        $preparedOptionData[static::getName()] = $this->baseHelper->jsonEncode($mageworxPrice);
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
            $parts = [];
            foreach ($datum as $datumKey => $datumValue) {
                $datumValue = $this->encodeSymbols($datumValue);
                $parts[]    = $datumKey . '=' . $datumValue . '';
            }
            $result[] = implode(',', $parts);
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

        $prices       = [];
        $preparedData = [];
        $iterator     = 0;

        $attributeData = $data['custom_option_' . $this->getName()];
        if (empty($attributeData)) {
            return $this->collectPrices([], $prices);
        }

        $step1 = explode('|', $attributeData);
        foreach ($step1 as $step1Item) {
            $step2 = explode(',', $step1Item);
            foreach ($step2 as $step2Item) {
                $step3Item                              = explode('=', $step2Item);
                $step3Item[1]                           = $this->decodeSymbols($step3Item[1]);
                $preparedData[$iterator][$step3Item[0]] = $step3Item[1];
            }
            $iterator++;
        }
        $prices[$data['custom_option_id']] = $this->baseHelper->jsonEncode($preparedData);

        return $this->collectPrices([], $prices);
    }
}
