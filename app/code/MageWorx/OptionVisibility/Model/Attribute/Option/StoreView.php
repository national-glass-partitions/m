<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionVisibility\Model\Attribute\Option;

use Magento\Framework\DataObjectFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Serialize\Serializer\Json as Serializer;
use MageWorx\OptionVisibility\Helper\Data as Helper;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionBase\Helper\System as SystemHelper;
use MageWorx\OptionVisibility\Model\OptionStoreView as StoreViewModel;
use MageWorx\OptionBase\Model\Product\Option\AbstractAttribute;

class StoreView extends AbstractAttribute
{
    const FIELD_MAGE_ONE_OPTIONS_IMPORT = '_custom_option_store_views';

    protected Helper $helper;
    protected SystemHelper $systemHelper;
    protected ResourceConnection $resource;
    /**
     * @var \MageWorx\OptionBase\Model\Entity\Group|\MageWorx\OptionBase\Model\Entity\Product
     */
    protected $entity;
    protected StoreViewModel $storeViewModel;
    protected Serializer $serializer;

    /**
     * @param ResourceConnection $resource
     * @param Helper $helper
     * @param BaseHelper $baseHelper
     * @param DataObjectFactory $dataObjectFactory
     * @param SystemHelper $systemHelper
     * @param StoreViewModel $storeViewModel
     * @param Serializer $serializer
     */
    public function __construct(
        ResourceConnection $resource,
        Helper $helper,
        StoreViewModel $storeViewModel,
        DataObjectFactory $dataObjectFactory,
        BaseHelper $baseHelper,
        SystemHelper $systemHelper,
        Serializer $serializer
    ) {
        $this->helper         = $helper;
        $this->systemHelper   = $systemHelper;
        $this->storeViewModel = $storeViewModel;
        $this->serializer     = $serializer;
        parent::__construct($resource, $baseHelper, $dataObjectFactory);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return StoreViewModel::KEY_STORE_VIEW;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
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
    public function getTableName($type = ''): string
    {
        $map = [
            'product' => StoreViewModel::TABLE_NAME,
            'group'   => StoreViewModel::OPTIONTEMPLATES_TABLE_NAME
        ];
        if (!$type) {
            return $map[$this->entity->getType()];
        }

        return (string)$map[$type];
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
        if (!$this->helper->isVisibilityStoreViewEnabled() && !$this->baseHelper->isAPOImportAction()) {
            return [];
        }

        $this->entity = $entity;

        $storeGroups = [];
        foreach ($options as $option) {
            if (empty($option) || !isset($option[$this->getName()])) {
                continue;
            }
            $storeGroups[$option['option_id']] = $option[$this->getName()];
        }

        return $this->collectStoreView($storeGroups);
    }

    /**
     * @param array $items
     * @return array
     */
    protected function collectStoreView($items)
    {
        $data     = [];
        $storeIds = $this->systemHelper->getStoreIds();

        foreach ($items as $itemKey => $itemValue) {
            $data['delete'][] = [
                StoreViewModel::COLUMN_NAME_OPTION_ID => $itemKey,
            ];
            if ($itemValue) {
                $decodedJsonData = $this->serializer->unserialize($itemValue);
            } else {
                $decodedJsonData = null;
            }
            if (empty($decodedJsonData) || !is_array($decodedJsonData)) {
                continue;
            }

            $isAllStores = false;
            foreach ($decodedJsonData as $key => $dataItem) {
                if ($dataItem[StoreViewModel::COLUMN_NAME_STORE_ID] == '0') {
                    $isAllStores = true;
                    break;
                }
            }
            if ($isAllStores) {
                continue;
            }

            foreach ($decodedJsonData as $key => $dataItem) {
                if (!in_array($dataItem[StoreViewModel::COLUMN_NAME_STORE_ID], array_values($storeIds))) {
                    continue;
                }
                $data['save'][] = [
                    StoreViewModel::COLUMN_NAME_OPTION_ID => $itemKey,
                    StoreViewModel::COLUMN_NAME_STORE_ID  =>
                        (int)$dataItem[StoreViewModel::COLUMN_NAME_STORE_ID]
                ];
            }
        }

        return $data;
    }

    /**
     * Delete old option value special prices
     *
     * @param array $data
     * @return void
     */
    public function deleteOldData(array $data)
    {
        $optionValueIds = [];
        foreach ($data as $dataItem) {
            $optionValueIds[] = $dataItem[StoreViewModel::COLUMN_NAME_OPTION_ID];
        }
        if (!$optionValueIds) {
            return;
        }
        $tableName  = $this->resource->getTableName($this->getTableName());
        $conditions = StoreViewModel::COLUMN_NAME_OPTION_ID .
            " IN (" . implode(",", $optionValueIds) . ")";
        $this->resource->getConnection()->delete($tableName, $conditions);
    }

    /**
     * {@inheritdoc}
     *
     * @param \Magento\Catalog\Model\Product\Option|\Magento\Catalog\Model\Product\Option\Value $object
     * @return array
     */
    public function prepareDataForFrontend($object)
    {
        return [];
    }

    /**
     * Process attribute in case of product/group duplication
     *
     * @param string $newId
     * @param string $oldId
     * @param string $entityType
     * @return void
     */
    public function processDuplicate($newId, $oldId, $entityType = 'product')
    {
        $connection = $this->resource->getConnection();
        $table      = $this->resource->getTableName($this->getTableName($entityType));

        $select = $connection->select()->from(
            $table,
            [
                new \Zend_Db_Expr($newId),
                StoreViewModel::COLUMN_NAME_STORE_ID
            ]
        )->where(
            StoreViewModel::COLUMN_NAME_OPTION_ID . ' = ?',
            $oldId
        );

        $insertSelect = $connection->insertFromSelect(
            $select,
            $table,
            [
                StoreViewModel::COLUMN_NAME_OPTION_ID,
                StoreViewModel::COLUMN_NAME_STORE_ID
            ]
        );
        $connection->query($insertSelect);
    }

    /**
     * {@inheritdoc}
     */
    public function importTemplateMageOne($data)
    {
        if (!isset($data['store_views']) || !is_array($data['store_views'])) {
            return $this->serializer->serialize([]);
        }
        $preparedData = [];
        foreach ($data['store_views'] as $storeId) {
            $preparedData[] = [
                StoreViewModel::COLUMN_NAME_STORE_ID => $storeId
            ];
        }

        return $this->serializer->serialize($preparedData);
    }

    /**
     * {@inheritdoc}
     */
    public function importTemplateMageTwo($data)
    {
        if (!isset($data['store_view']) || !is_array($data['store_view'])) {
            return $this->serializer->serialize([]);
        }
        $preparedData = [];
        foreach ($data['store_view'] as $storeId) {
            $preparedData[] = [
                StoreViewModel::COLUMN_NAME_STORE_ID => $storeId
            ];
        }

        return $this->serializer->serialize($preparedData);
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
        if (!isset($optionData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT])
            || $optionData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT] === ''
        ) {
            return;
        }

        $storeViews = explode(',', $optionData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT]);
        foreach ($storeViews as $storeView) {
            $systemData['store'][$storeView] = $storeView;
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
        return $this->collectStoresDataByKey($data, 'store_view');
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
        if (!isset($optionData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT])
            || $optionData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT] === ''
        ) {
            return;
        }

        $isStoreExist = false;
        $data         = [];
        $storeViews   = explode(',', $optionData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT]);
        foreach ($storeViews as $storeView) {
            if (!$this->hasStoreEquivalent($systemData, $storeView)) {
                continue;
            }
            $isStoreExist = true;
            $data[]       = [
                StoreViewModel::COLUMN_NAME_STORE_ID => $systemData['map']['store'][$storeView]
            ];
        }
        if (!$isStoreExist) {
            $preparedOptionData['disabled'] = 1;
        }
        $preparedOptionData[static::getName()] = $this->baseHelper->jsonEncode($data);
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

        $storeViews   = [];
        $preparedData = [];
        $iterator     = 0;

        $attributeData = $data['custom_option_' . $this->getName()];
        if (empty($attributeData)) {
            return $this->collectStoreView($storeViews);
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
        $storeViews[$data['custom_option_id']] = $this->baseHelper->jsonEncode($preparedData);
        return $this->collectStoreView($storeViews);
    }
}
