<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionAdvancedPricing\Model\Attribute\Option\Value;

use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Serialize\Serializer\Json as Serializer;
use MageWorx\OptionAdvancedPricing\Helper\Data as Helper;
use MageWorx\OptionAdvancedPricing\Model\TierPrice as TierPriceModel;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionBase\Helper\System as SystemHelper;
use MageWorx\OptionBase\Model\Product\Option\AbstractAttribute;

class TierPrice extends AbstractAttribute
{
    const FIELD_MAGE_ONE_OPTIONS_IMPORT = '_custom_option_row_tier_data';

    protected Helper $helper;
    protected SystemHelper $systemHelper;
    protected TierPriceModel $tierPriceModel;
    protected Serializer $serializer;

    public function __construct(
        ResourceConnection $resource,
        Helper $helper,
        BaseHelper $baseHelper,
        DataObjectFactory $dataObjectFactory,
        TierPriceModel $tierPriceModel,
        SystemHelper $systemHelper,
        Serializer $serializer
    ) {
        $this->helper         = $helper;
        $this->tierPriceModel = $tierPriceModel;
        $this->systemHelper   = $systemHelper;
        $this->serializer     = $serializer;
        parent::__construct($resource, $baseHelper, $dataObjectFactory);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return TierPriceModel::KEY_TIER_PRICE;
    }

    /**
     * {@inheritdoc}
     */
    public function hasOwnTable()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getTableName($type = '')
    {
        $map = [
            'product' => TierPriceModel::TABLE_NAME,
            'group'   => TierPriceModel::OPTIONTEMPLATES_TABLE_NAME
        ];
        if (!$type) {
            return $map[$this->entity->getType()];
        }

        return $map[$type];
    }

    /**
     * {@inheritdoc}
     */
    public function collectData($entity, array $options)
    {
        if (!$this->helper->isTierPriceEnabled() && !$this->baseHelper->isAPOImportAction()) {
            return [];
        }

        $this->entity = $entity;

        $tierPrices = [];
        foreach ($options as $option) {
            if (empty($option['values'])) {
                continue;
            }
            foreach ($option['values'] as $value) {
                if (!isset($value[$this->getName()])) {
                    continue;
                }
                $tierPrices[$value[TierPriceModel::COLUMN_OPTION_TYPE_ID]] = $value[$this->getName()];
            }
        }

        return $this->collectTierPrices($tierPrices);
    }

    /**
     * Collect tier prices
     *
     * @param array $items
     * @return array
     */
    protected function collectTierPrices($items)
    {
        $customerGroupIds = $this->systemHelper->getCustomerGroupIds(true);
        $data             = [];

        foreach ($items as $itemKey => $itemValue) {
            $data['delete'][] = [
                TierPriceModel::COLUMN_OPTION_TYPE_ID => $itemKey,
            ];
            $decodedJsonData  = $itemValue ? $this->serializer->unserialize($itemValue) : null;
            if (empty($decodedJsonData) || !is_array($decodedJsonData)) {
                continue;
            }
            foreach ($decodedJsonData as $dataItem) {
                if (!in_array(
                    $dataItem[TierPriceModel::COLUMN_CUSTOMER_GROUP_ID],
                    array_values($customerGroupIds)
                )) {
                    continue;
                }
                $dateFrom  = $dataItem[TierPriceModel::COLUMN_DATE_FROM] ?: null;
                $dateTo    = $dataItem[TierPriceModel::COLUMN_DATE_TO] ?: null;
                $price     = $dataItem[TierPriceModel::COLUMN_PRICE];
                $priceType = $dataItem[TierPriceModel::COLUMN_PRICE_TYPE];
                if ($priceType == Helper::PRICE_TYPE_PERCENTAGE_DISCOUNT) {
                    $price = abs($price);
                }
                $customerGroup = (string)$dataItem[TierPriceModel::COLUMN_CUSTOMER_GROUP_ID];
                if (!strlen($customerGroup)) {
                    $customerGroup = \Magento\Customer\Model\Group::CUST_GROUP_ALL;
                } else {
                    $customerGroup = (int)$customerGroup;
                }
                $data['save'][] = [
                    TierPriceModel::COLUMN_OPTION_TYPE_ID    => $itemKey,
                    TierPriceModel::COLUMN_CUSTOMER_GROUP_ID => $customerGroup,
                    TierPriceModel::COLUMN_PRICE             => $price,
                    TierPriceModel::COLUMN_PRICE_TYPE        => $priceType,
                    TierPriceModel::COLUMN_QTY               => $dataItem[TierPriceModel::COLUMN_QTY],
                    TierPriceModel::COLUMN_DATE_FROM         => $dateFrom,
                    TierPriceModel::COLUMN_DATE_TO           => $dateTo,
                ];
            }
        }

        return $data;
    }

    /**
     * Delete old option value tier prices
     *
     * @param $data
     * @return void
     */
    public function deleteOldData(array $data)
    {
        $optionValueIds = [];
        foreach ($data as $dataItem) {
            $optionValueIds[] = $dataItem[TierPriceModel::COLUMN_OPTION_TYPE_ID];
        }
        if (!$optionValueIds) {
            return;
        }
        $tableName  = $this->resource->getTableName($this->getTableName());
        $conditions = TierPriceModel::COLUMN_OPTION_TYPE_ID .
            " IN (" . implode(",", $optionValueIds) . ")";
        $this->resource->getConnection()->delete($tableName, $conditions);
    }

    /**
     * {@inheritdoc}
     */
    public function prepareDataForFrontend($object)
    {
        $tierPrices = $this->tierPriceModel->getSuitableTierPrices($object, true);
        if (!$tierPrices) {
            return [];
        } else {
            return [$this->getName() => $this->serializer->serialize($tierPrices)];
        }
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
                TierPriceModel::COLUMN_CUSTOMER_GROUP_ID,
                TierPriceModel::COLUMN_QTY,
                TierPriceModel::COLUMN_PRICE,
                TierPriceModel::COLUMN_PRICE_TYPE,
                TierPriceModel::COLUMN_DATE_FROM,
                TierPriceModel::COLUMN_DATE_TO
            ]
        )->where(
            TierPriceModel::COLUMN_OPTION_TYPE_ID . ' = ?',
            $oldId
        );

        $insertSelect = $connection->insertFromSelect(
            $select,
            $table,
            [
                TierPriceModel::COLUMN_OPTION_TYPE_ID,
                TierPriceModel::COLUMN_CUSTOMER_GROUP_ID,
                TierPriceModel::COLUMN_QTY,
                TierPriceModel::COLUMN_PRICE,
                TierPriceModel::COLUMN_PRICE_TYPE,
                TierPriceModel::COLUMN_DATE_FROM,
                TierPriceModel::COLUMN_DATE_TO
            ]
        );
        $connection->query($insertSelect);
    }

    /**
     * {@inheritdoc}
     */
    public function validateTemplateMageOne($data)
    {
        if (!isset($data['tiers']) || !is_array($data['tiers'])) {
            return true;
        }

        foreach ($data['tiers'] as $tierPriceItem) {
            if (!isset($tierPriceItem['customer_group_id'])) {
                throw new LocalizedException(
                    __("Tier price's field '%1' not found", 'customer_group_id')
                );
            }
            if (!isset($tierPriceItem['price'])) {
                throw new LocalizedException(
                    __("Tier price's field '%1' not found", 'price')
                );
            }
            if (!isset($tierPriceItem['price_type'])) {
                throw new LocalizedException(
                    __("Tier price's field '%1' not found", 'price_type')
                );
            }
            if (!isset($tierPriceItem['qty'])) {
                throw new LocalizedException(
                    __("Tier price's field '%1' not found", 'qty')
                );
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function importTemplateMageOne($data)
    {
        $tierPrices = [];
        if (!isset($data['tiers']) || !is_array($data['tiers'])) {
            return '';
        }

        foreach ($data['tiers'] as $tierPriceItem) {
            if ($tierPriceItem['price_type'] == 'percent') {
                $price     = 100 - $tierPriceItem['price'];
                $priceType = Helper::PRICE_TYPE_PERCENTAGE_DISCOUNT;
            } else {
                $price     = $tierPriceItem['price'];
                $priceType = $tierPriceItem['price_type'];
            }
            $tierPrices[] = [
                TierPriceModel::COLUMN_CUSTOMER_GROUP_ID => $tierPriceItem['customer_group_id'],
                TierPriceModel::COLUMN_PRICE             => $price,
                TierPriceModel::COLUMN_PRICE_TYPE        => $priceType,
                TierPriceModel::COLUMN_QTY               => $tierPriceItem['qty'],
                TierPriceModel::COLUMN_DATE_FROM         => '',
                TierPriceModel::COLUMN_DATE_TO           => '',
            ];
        }

        return $this->serializer->serialize($tierPrices);
    }

    /**
     * {@inheritdoc}
     */
    public function importTemplateMageTwo($data)
    {
        return isset($data[$this->getName()]) ? $this->serializer->serialize($data[$this->getName()]) : null;
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
        if (!isset($valueData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT])
            || $valueData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT] === ''
        ) {
            return;
        }

        if (is_array($valueData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT])) {
            if (isset($valueData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT][0])) {
                $valueData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT] = $valueData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT][0];
            } else {
                return;
            }
        }

        $tierPrices = explode('|', $valueData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT]);
        foreach ($tierPrices as $tierPrice) {
            list($customerGroupId, $qty, $price, $priceType) = explode(':', $tierPrice);
            if ($this->baseHelper->isAllCustomerGroupId((string)$customerGroupId)) {
                continue;
            }
            $systemData['customer_group'][$customerGroupId] = $customerGroupId;
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
        if (!isset($valueData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT])
            || $valueData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT] === ''
        ) {
            return;
        }

        if (is_array($valueData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT])) {
            if (isset($valueData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT][0])) {
                $valueData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT] = $valueData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT][0];
            } else {
                return;
            }
        }

        $data       = [];
        $tierPrices = explode('|', $valueData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT]);
        foreach ($tierPrices as $tierPrice) {
            list($customerGroupId, $qty, $price, $priceType) = explode(':', $tierPrice);
            $isAllGroups = $this->baseHelper->isAllCustomerGroupId((string)$customerGroupId);

            if (!$this->hasCustomerGroupEquivalent($systemData, $customerGroupId) && !$isAllGroups) {
                continue;
            }
            $data[] = [
                TierPriceModel::COLUMN_CUSTOMER_GROUP_ID => $isAllGroups
                    ? $customerGroupId
                    : $systemData['map']['customer_group'][$customerGroupId],
                TierPriceModel::COLUMN_PRICE             => $price,
                TierPriceModel::COLUMN_PRICE_TYPE        => $priceType,
                TierPriceModel::COLUMN_QTY               => $qty,
                TierPriceModel::COLUMN_DATE_FROM         => '',
                TierPriceModel::COLUMN_DATE_TO           => ''

            ];
        }
        $preparedValueData[static::getName()] = $this->baseHelper->jsonEncode($data);
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

        if (!isset($data['custom_option_row_' . $this->getName()])) {
            return null;
        }

        $this->entity = $this->dataObjectFactory->create();
        $this->entity->setType('product');

        $tierPrices   = [];
        $preparedData = [];
        $iterator     = 0;

        $attributeData = $data['custom_option_row_' . $this->getName()];
        if (empty($attributeData)) {
            return $this->collectTierPrices($tierPrices);
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
        $tierPrices[$data['custom_option_row_id']] = $this->baseHelper->jsonEncode($preparedData);

        return $this->collectTierPrices($tierPrices);
    }
}
