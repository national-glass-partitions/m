<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionBase\Model\Attribute\Option;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Serialize\Serializer\Json as Serializer;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionBase\Model\OptionTitle;
use MageWorx\OptionBase\Model\Product\Option\AbstractAttribute;

class Title extends AbstractAttribute
{
    const FIELD_IS_USE_DEFAULT          = 'is_use_default';
    const FIELD_MAGE_ONE_OPTIONS_IMPORT = '_custom_option_title';

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
        return OptionTitle::KEY_MAGEWORX_OPTION_TITLE;
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
            'product' => OptionTitle::TABLE_NAME,
            'group'   => OptionTitle::OPTIONTEMPLATES_TABLE_NAME,
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
        $this->entity   = $entity;
        $currentStoreId = $entity->getDataObject()->getData('store_id') ?: 0;

        $savedItems = [];
        $items      = [];
        foreach ($options as $option) {
            if (empty($option[$this->getName()])) {
                continue;
            }
            $savedItems[$option[OptionTitle::FIELD_OPTION_ID]] = $option[$this->getName()];

            $title = empty($option[self::FIELD_IS_USE_DEFAULT]) ? $option[OptionTitle::FIELD_TITLE] : '';

            $items[$option[OptionTitle::FIELD_OPTION_ID]] = [
                $currentStoreId => $title
            ];
        }

        return $this->collectTitles($items, $savedItems);
    }

    /**
     * Collect option value titles
     *
     * @param array $items
     * @param array $savedItems
     * @return array
     */
    protected function collectTitles($items, $savedItems)
    {
        $data = [];

        foreach ($savedItems as $savedItemKey => $savedItemValue) {
            $savedItemValue  = preg_replace('/[[:cntrl:]]/', ' ', $savedItemValue);
            $decodedJsonData = $savedItemValue ? $this->serializer->unserialize($savedItemValue) : null;

            if (empty($decodedJsonData)) {
                continue;
            }
            $data['delete'][] = [
                OptionTitle::FIELD_OPTION_ID => $savedItemKey,
            ];
            $this->mergeNewTitles($decodedJsonData, $items, $savedItemKey);
            foreach ($decodedJsonData as $dataItem) {
                $title          = str_replace('&quot;', '"', $dataItem[OptionTitle::FIELD_TITLE]);
                $data['save'][] = [
                    OptionTitle::FIELD_OPTION_ID => $savedItemKey,
                    OptionTitle::FIELD_STORE_ID  => $dataItem[OptionTitle::FIELD_STORE_ID],
                    OptionTitle::FIELD_TITLE     => $title,
                ];
            }
        }

        return $data;
    }

    /**
     * Merge new titles with the old ones
     * Prepare data before save to db, used because we re-insert all titles for all store views
     *
     * @param array $decodedJsonData
     * @param array $items
     * @param int $savedItemKey
     */
    protected function mergeNewTitles(&$decodedJsonData, $items, $savedItemKey)
    {
        foreach ($items as $itemKey => $itemData) {
            if ($itemKey != $savedItemKey) {
                continue;
            }
            foreach ($itemData as $storeId => $storeTitle) {
                if ($storeTitle === '') {
                    if (is_array($decodedJsonData)) {
                        foreach ($decodedJsonData as $key => $value) {
                            if ($value['store_id'] == $storeId) {
                                unset($decodedJsonData[$key]);
                            }
                        }
                    }
                    continue;
                }
                $isSaved = false;
                foreach ($decodedJsonData as $dataKey => $dataItem) {
                    if ($dataItem[OptionTitle::FIELD_STORE_ID] == $storeId) {
                        $decodedJsonData[$dataKey][OptionTitle::FIELD_TITLE] = $storeTitle;
                        $isSaved                                             = true;
                    }
                }
                if ($isSaved) {
                    continue;
                }
                $decodedJsonData[] = [
                    OptionTitle::FIELD_STORE_ID => $storeId,
                    OptionTitle::FIELD_TITLE    => $storeTitle,
                ];
            }
        }
    }

    /**
     * Delete old mageworx option value titles
     *
     * @param array $data
     * @return void
     */
    public function deleteOldData(array $data)
    {
        $optionIds = [];
        foreach ($data as $dataItem) {
            $optionIds[] = $dataItem[OptionTitle::FIELD_OPTION_ID];
        }
        if (!$optionIds) {
            return;
        }
        $tableName  = $this->resource->getTableName($this->getTableName());
        $conditions = OptionTitle::FIELD_OPTION_ID . " IN (" . implode(',', $optionIds) . ")";
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
        return $this->collectStoresDataByKey($data, 'mageworx_title');
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

        $mageworxTitle = [];
        foreach ($optionData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT] as $datumStore => $datumValue) {
            if (!$this->hasStoreEquivalent($systemData, $datumStore)) {
                continue;
            }
            $mageworxTitle[] = [
                OptionTitle::FIELD_STORE_ID => $systemData['map']['store'][$datumStore],
                OptionTitle::FIELD_TITLE    => $datumValue,
            ];
        }
        $preparedOptionData[static::getName()] = $this->baseHelper->jsonEncode($mageworxTitle);
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

        $titles       = [];
        $preparedData = [];
        $iterator     = 0;

        $attributeData = $data['custom_option_' . $this->getName()];
        if (empty($attributeData)) {
            return $this->collectTitles([], $titles);
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
        $titles[$data['custom_option_id']] = $this->baseHelper->jsonEncode($preparedData);

        return $this->collectTitles([], $titles);
    }
}
