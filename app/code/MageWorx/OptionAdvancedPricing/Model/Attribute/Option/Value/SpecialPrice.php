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
use MageWorx\OptionAdvancedPricing\Model\SpecialPrice as SpecialPriceModel;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionBase\Helper\System as SystemHelper;
use MageWorx\OptionBase\Model\Product\Option\AbstractAttribute;

class SpecialPrice extends AbstractAttribute
{
    const FIELD_MAGE_ONE_OPTIONS_IMPORT = '_custom_option_row_special_data';

    protected Helper $helper;
    protected SystemHelper $systemHelper;
    protected SpecialPriceModel $specialPriceModel;
    protected Serializer $serializer;

    /**
     * @param ResourceConnection $resource
     * @param Helper $helper
     * @param BaseHelper $baseHelper
     * @param DataObjectFactory $dataObjectFactory
     * @param SystemHelper $systemHelper
     * @param SpecialPriceModel $specialPriceModel
     * @param Serializer $serializer
     */
    public function __construct(
        ResourceConnection $resource,
        Helper $helper,
        BaseHelper $baseHelper,
        DataObjectFactory $dataObjectFactory,
        SpecialPriceModel $specialPriceModel,
        SystemHelper $systemHelper,
        Serializer $serializer
    ) {
        $this->helper            = $helper;
        $this->systemHelper      = $systemHelper;
        $this->specialPriceModel = $specialPriceModel;
        $this->serializer        = $serializer;
        parent::__construct($resource, $baseHelper, $dataObjectFactory);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return SpecialPriceModel::KEY_SPECIAL_PRICE;
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
            'product' => SpecialPriceModel::TABLE_NAME,
            'group'   => SpecialPriceModel::OPTIONTEMPLATES_TABLE_NAME
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
        if (!$this->helper->isSpecialPriceEnabled() && !$this->baseHelper->isAPOImportAction()) {
            return [];
        }

        $this->entity = $entity;

        $specialPrices = [];
        foreach ($options as $option) {
            if (empty($option['values'])) {
                continue;
            }
            foreach ($option['values'] as $value) {
                if (!isset($value[$this->getName()])) {
                    continue;
                }
                $specialPrices[$value[SpecialPriceModel::COLUMN_OPTION_TYPE_ID]] = $value[$this->getName()];
            }
        }

        return $this->collectSpecialPrices($specialPrices);
    }

    /**
     * Collect special prices
     *
     * @param array $items
     * @return array
     */
    protected function collectSpecialPrices($items)
    {
        $customerGroupIds = $this->systemHelper->getCustomerGroupIds(true);
        $data             = [];

        foreach ($items as $itemKey => $itemValue) {
            $data['delete'][] = [
                SpecialPriceModel::COLUMN_OPTION_TYPE_ID => $itemKey,
            ];
            $decodedJsonData  = $itemValue ? $this->serializer->unserialize($itemValue) : [];

            if (empty($decodedJsonData) || !is_array($decodedJsonData)) {
                continue;
            }
            foreach ($decodedJsonData as $dataItem) {
                if (!in_array(
                    $dataItem[SpecialPriceModel::COLUMN_CUSTOMER_GROUP_ID],
                    array_values($customerGroupIds)
                )) {
                    continue;
                }
                $dateFrom  = $dataItem[SpecialPriceModel::COLUMN_DATE_FROM] ?: null;
                $dateTo    = $dataItem[SpecialPriceModel::COLUMN_DATE_TO] ?: null;
                $comment   = str_replace('\\', '', $dataItem[SpecialPriceModel::COLUMN_COMMENT]);
                $comment   = htmlspecialchars(
                    $comment,
                    ENT_COMPAT,
                    'UTF-8',
                    false
                );
                $price     = $dataItem[SpecialPriceModel::COLUMN_PRICE];
                $priceType = $dataItem[SpecialPriceModel::COLUMN_PRICE_TYPE];
                if ($priceType == Helper::PRICE_TYPE_PERCENTAGE_DISCOUNT) {
                    $price = abs($price);
                }
                $customerGroup = (string)$dataItem[SpecialPriceModel::COLUMN_CUSTOMER_GROUP_ID];
                if (!strlen($customerGroup)) {
                    $customerGroup = \Magento\Customer\Model\Group::CUST_GROUP_ALL;
                } else {
                    $customerGroup = (int)$customerGroup;
                }
                $data['save'][] = [
                    SpecialPriceModel::COLUMN_OPTION_TYPE_ID    => $itemKey,
                    SpecialPriceModel::COLUMN_CUSTOMER_GROUP_ID => $customerGroup,
                    SpecialPriceModel::COLUMN_PRICE             => $price,
                    SpecialPriceModel::COLUMN_PRICE_TYPE        => $priceType,
                    SpecialPriceModel::COLUMN_COMMENT           => $comment,
                    SpecialPriceModel::COLUMN_DATE_FROM         => $dateFrom,
                    SpecialPriceModel::COLUMN_DATE_TO           => $dateTo,
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
            $optionValueIds[] = $dataItem[SpecialPriceModel::COLUMN_OPTION_TYPE_ID];
        }
        if (!$optionValueIds) {
            return;
        }
        $tableName  = $this->resource->getTableName($this->getTableName());
        $conditions = SpecialPriceModel::COLUMN_OPTION_TYPE_ID .
            " IN (" . implode(",", $optionValueIds) . ")";
        $this->resource->getConnection()->delete($tableName, $conditions);
    }

    /**
     * {@inheritdoc}
     */
    public function prepareDataForFrontend($object)
    {
        return [$this->getName() => $this->specialPriceModel->getActualSpecialPrice($object, true)];
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
                SpecialPriceModel::COLUMN_CUSTOMER_GROUP_ID,
                SpecialPriceModel::COLUMN_PRICE,
                SpecialPriceModel::COLUMN_PRICE_TYPE,
                SpecialPriceModel::COLUMN_COMMENT,
                SpecialPriceModel::COLUMN_DATE_FROM,
                SpecialPriceModel::COLUMN_DATE_TO
            ]
        )->where(
            SpecialPriceModel::COLUMN_OPTION_TYPE_ID . ' = ?',
            $oldId
        );

        $insertSelect = $connection->insertFromSelect(
            $select,
            $table,
            [
                SpecialPriceModel::COLUMN_OPTION_TYPE_ID,
                SpecialPriceModel::COLUMN_CUSTOMER_GROUP_ID,
                SpecialPriceModel::COLUMN_PRICE,
                SpecialPriceModel::COLUMN_PRICE_TYPE,
                SpecialPriceModel::COLUMN_COMMENT,
                SpecialPriceModel::COLUMN_DATE_FROM,
                SpecialPriceModel::COLUMN_DATE_TO
            ]
        );
        $connection->query($insertSelect);
    }

    /**
     * {@inheritdoc}
     */
    public function validateTemplateMageOne($data)
    {
        if (!isset($data['specials']) || !is_array($data['specials'])) {
            return true;
        }

        foreach ($data['specials'] as $specialPriceItem) {
            if (!isset($specialPriceItem['customer_group_id'])) {
                throw new LocalizedException(
                    __("Special price's field '%1' not found", 'customer_group_id')
                );
            }
            if (!isset($specialPriceItem['price'])) {
                throw new LocalizedException(
                    __("Special price's field '%1' not found", 'price')
                );
            }
            if (!isset($specialPriceItem['price_type'])) {
                throw new LocalizedException(
                    __("Special price's field '%1' not found", 'price_type')
                );
            }
            if (!isset($specialPriceItem['comment'])) {
                throw new LocalizedException(
                    __("Special price's field '%1' not found", 'comment')
                );
            }
            if (!isset($specialPriceItem['date_from'])) {
                throw new LocalizedException(
                    __("Special price's field '%1' not found", 'date_from')
                );
            }
            if (!isset($specialPriceItem['date_to'])) {
                throw new LocalizedException(
                    __("Special price's field '%1' not found", 'date_to')
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
        $specialPrices = [];
        if (!isset($data['specials']) || !is_array($data['specials'])) {
            return '';
        }

        foreach ($data['specials'] as $specialPriceItem) {
            if ($specialPriceItem['price_type'] == 'percent') {
                $price     = 100 - $specialPriceItem['price'];
                $priceType = Helper::PRICE_TYPE_PERCENTAGE_DISCOUNT;
            } else {
                $price     = $specialPriceItem['price'];
                $priceType = $specialPriceItem['price_type'];
            }
            $specialPrices[] = [
                SpecialPriceModel::COLUMN_CUSTOMER_GROUP_ID => $specialPriceItem['customer_group_id'],
                SpecialPriceModel::COLUMN_PRICE             => $price,
                SpecialPriceModel::COLUMN_PRICE_TYPE        => $priceType,
                SpecialPriceModel::COLUMN_COMMENT           => $specialPriceItem['comment'],
                SpecialPriceModel::COLUMN_DATE_FROM         => $specialPriceItem['date_from'],
                SpecialPriceModel::COLUMN_DATE_TO           => $specialPriceItem['date_to'],
            ];
        }

        return $this->serializer->serialize($specialPrices);
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

        $specialPrices = explode('|', $valueData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT]);
        foreach ($specialPrices as $specialPrice) {
            list($customerGroupId, $price, $priceType, $comment) = explode(':', $specialPrice);
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

        $data          = [];
        $specialPrices = explode('|', $valueData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT]);
        foreach ($specialPrices as $specialPrice) {
            list($customerGroupId, $price, $priceType, $comment) = explode(':', $specialPrice);
            $isAllGroups = $this->baseHelper->isAllCustomerGroupId((string)$customerGroupId);

            if (!$this->hasCustomerGroupEquivalent($systemData, $customerGroupId) && !$isAllGroups) {
                continue;
            }

            $data[] = [
                SpecialPriceModel::COLUMN_CUSTOMER_GROUP_ID => $isAllGroups
                    ? $customerGroupId
                    : $systemData['map']['customer_group'][$customerGroupId],
                SpecialPriceModel::COLUMN_PRICE             => $price,
                SpecialPriceModel::COLUMN_PRICE_TYPE        => $priceType,
                SpecialPriceModel::COLUMN_COMMENT           => $comment,
                SpecialPriceModel::COLUMN_DATE_FROM         => '',
                SpecialPriceModel::COLUMN_DATE_TO           => ''

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

        $prices       = [];
        $preparedData = [];
        $iterator     = 0;

        $attributeData = $data['custom_option_row_' . $this->getName()];
        if (empty($attributeData)) {
            return $this->collectSpecialPrices($prices);
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
        $prices[$data['custom_option_row_id']] = $this->baseHelper->jsonEncode($preparedData);

        return $this->collectSpecialPrices($prices);
    }
}
