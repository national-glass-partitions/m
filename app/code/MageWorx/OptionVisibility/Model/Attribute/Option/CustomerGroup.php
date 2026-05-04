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
use MageWorx\OptionBase\Helper\System as SystemHelper;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionVisibility\Model\OptionCustomerGroup as CustomerGroupModel;
use MageWorx\OptionBase\Model\Product\Option\AbstractAttribute;

class CustomerGroup extends AbstractAttribute
{
    const FIELD_MAGE_ONE_OPTIONS_IMPORT = '_custom_option_customer_groups';

    protected Helper $helper;
    protected SystemHelper $systemHelper;
    /**
     * @var \MageWorx\OptionBase\Model\Entity\Group|\MageWorx\OptionBase\Model\Entity\Product
     */
    protected $entity;
    protected CustomerGroupModel $customerGroupModel;
    protected Serializer $serializer;

    /**
     * @param ResourceConnection $resource
     * @param Helper $helper
     * @param BaseHelper $baseHelper
     * @param DataObjectFactory $dataObjectFactory
     * @param SystemHelper $systemHelper
     * @param CustomerGroupModel $customerGroupModel
     * @param Serializer $serializer
     */
    public function __construct(
        ResourceConnection $resource,
        Helper $helper,
        CustomerGroupModel $customerGroupModel,
        DataObjectFactory $dataObjectFactory,
        BaseHelper $baseHelper,
        SystemHelper $systemHelper,
        Serializer $serializer
    ) {
        $this->helper             = $helper;
        $this->systemHelper       = $systemHelper;
        $this->customerGroupModel = $customerGroupModel;
        $this->serializer         = $serializer;
        parent::__construct($resource, $baseHelper, $dataObjectFactory);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return CustomerGroupModel::KEY_CUSTOMER_GROUP;
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
            'product' => CustomerGroupModel::TABLE_NAME,
            'group'   => CustomerGroupModel::OPTIONTEMPLATES_TABLE_NAME
        ];
        if (!$type) {
            return (string)$map[$this->entity->getType()];
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
        if (!$this->helper->isVisibilityCustomerGroupEnabled() && !$this->baseHelper->isAPOImportAction()) {
            return [];
        }

        $this->entity = $entity;

        $customerGroups = [];
        foreach ($options as $option) {
            if (empty($option) || !isset($option[$this->getName()])) {
                continue;
            }
            $customerGroups[$option['option_id']] = $option[$this->getName()];
        }

        return $this->collectCustomerGroup($customerGroups);
    }

    /**
     * @param array $items
     * @return array
     */
    protected function collectCustomerGroup($items)
    {
        $data             = [];
        $customerGroupIds = $this->systemHelper->getCustomerGroupIds();

        foreach ($items as $itemKey => $itemValue) {
            $data['delete'][] = [
                CustomerGroupModel::COLUMN_NAME_OPTION_ID => $itemKey,
            ];

            $decodedJsonData = $itemValue ? $this->serializer->unserialize($itemValue) : null;

            if (empty($decodedJsonData) || !is_array($decodedJsonData)) {
                continue;
            }

            $isAllGroups = false;
            foreach ($decodedJsonData as $key => $dataItem) {
                if ($this->baseHelper->isAllCustomerGroupId(
                    (string)$dataItem[CustomerGroupModel::COLUMN_NAME_GROUP_ID]
                )) {
                    $isAllGroups = true;
                    break;
                }
            }
            if ($isAllGroups) {
                continue;
            }

            foreach ($decodedJsonData as $key => $dataItem) {
                if (!in_array($dataItem[CustomerGroupModel::COLUMN_NAME_GROUP_ID], array_values($customerGroupIds))) {
                    continue;
                }
                $data['save'][] = [
                    CustomerGroupModel::COLUMN_NAME_OPTION_ID => $itemKey,
                    CustomerGroupModel::COLUMN_NAME_GROUP_ID  =>
                        (int)$dataItem[CustomerGroupModel::COLUMN_NAME_GROUP_ID]
                ];
            }
        }

        return $data;
    }

    /**
     * Delete old option value
     *
     * @param array $data
     * @return void
     */
    public function deleteOldData(array $data)
    {
        $optionValueIds = [];
        foreach ($data as $dataItem) {
            $optionValueIds[] = $dataItem[CustomerGroupModel::COLUMN_NAME_OPTION_ID];
        }
        if (!$optionValueIds) {
            return;
        }
        $tableName  = $this->resource->getTableName($this->getTableName());
        $conditions = CustomerGroupModel::COLUMN_NAME_OPTION_ID .
            " IN (" . implode(",", $optionValueIds) . ")";
        $this->resource->getConnection()->delete($tableName, $conditions);
    }

    /**
     * {@inheritdoc}
     *
     * @param \Magento\Catalog\Model\Product\Option|\Magento\Catalog\Model\Product\Option\Value|array $data
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
                CustomerGroupModel::COLUMN_NAME_GROUP_ID
            ]
        )->where(
            CustomerGroupModel::COLUMN_NAME_OPTION_ID . ' = ?',
            $oldId
        );

        $insertSelect = $connection->insertFromSelect(
            $select,
            $table,
            [
                CustomerGroupModel::COLUMN_NAME_OPTION_ID,
                CustomerGroupModel::COLUMN_NAME_GROUP_ID
            ]
        );
        $connection->query($insertSelect);
    }

    /**
     * {@inheritdoc}
     */
    public function importTemplateMageOne($data)
    {
        $preparedData = [];
        if (!isset($data['customer_groups']) || !is_array($data['customer_groups'])) {
            return $this->serializer->serialize($preparedData);
        }
        foreach ($data['customer_groups'] as $customerGroupId) {
            $preparedData[] = [
                CustomerGroupModel::COLUMN_NAME_GROUP_ID => $customerGroupId
            ];
        }

        return $this->serializer->serialize($preparedData);
    }

    /**
     * {@inheritdoc}
     */
    public function importTemplateMageTwo($data)
    {
        $preparedData = [];
        if (!isset($data['customer_group']) || !is_array($data['customer_group'])) {
            return $this->serializer->serialize($preparedData);
        }
        foreach ($data['customer_group'] as $customerGroupId) {
            $preparedData[] = [
                CustomerGroupModel::COLUMN_NAME_GROUP_ID => $customerGroupId
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

        $customerGroups = explode(',', $optionData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT]);
        foreach ($customerGroups as $customerGroup) {
            $systemData['customer_group'][$customerGroup] = $customerGroup;
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
        return $this->collectCustomerGroupsDataByKey($data, 'customer_group');
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

        $isCustomerGroupExist = false;
        $data                 = [];
        $customerGroups       = explode(',', $optionData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT]);
        foreach ($customerGroups as $customerGroup) {
            if (!$this->hasCustomerGroupEquivalent($systemData, $customerGroup)) {
                continue;
            }
            $isCustomerGroupExist = true;
            $data[]               = [
                CustomerGroupModel::COLUMN_NAME_GROUP_ID => $systemData['map']['customer_group'][$customerGroup]
            ];
        }
        if (!$isCustomerGroupExist) {
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

        $customerGroups = [];
        $preparedData   = [];
        $iterator       = 0;

        $attributeData = $data['custom_option_' . $this->getName()];
        if (empty($attributeData)) {
            return $this->collectCustomerGroup($customerGroups);
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
        $customerGroups[$data['custom_option_id']] = $this->baseHelper->jsonEncode($preparedData);

        return $this->collectCustomerGroup($customerGroups);
    }
}
