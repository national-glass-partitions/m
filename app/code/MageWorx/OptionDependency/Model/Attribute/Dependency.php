<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionDependency\Model\Attribute;

use Magento\Framework\DataObjectFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Serialize\Serializer\Json as Serializer;
use Magento\Framework\Registry;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionBase\Model\Product\Option\AbstractAttribute;
use MageWorx\OptionDependency\Helper\Data as Helper;
use MageWorx\OptionDependency\Model\Config;
use MageWorx\OptionDependency\Model\Converter;
use MageWorx\OptionDependency\Model\ResourceModel\ProcessDeleteOldData as ProcessDeleteOldData;

class Dependency extends AbstractAttribute
{
    protected string $saveSql = '';
    protected Helper $helper;
    protected Serializer $serializer;
    protected array $options = [];
    protected Converter $converter;
    protected Registry $registry;
    protected bool $isAfterTemplate = false;
    protected bool $isProcessingDependencyRules = false;
    protected ProcessDeleteOldData $processDeleteOldData;

    /**
     * Dependency constructor.
     *
     * @param ResourceConnection $resource
     * @param Helper $helper
     * @param Converter $converter
     * @param Registry $registry
     * @param BaseHelper $baseHelper
     * @param DataObjectFactory $dataObjectFactory
     * @param Serializer $serializer
     * @param ProcessDeleteOldData $processDeleteOldData
     */
    public function __construct(
        ResourceConnection $resource,
        Helper $helper,
        Converter $converter,
        Registry $registry,
        BaseHelper $baseHelper,
        DataObjectFactory $dataObjectFactory,
        Serializer $serializer,
        ProcessDeleteOldData $processDeleteOldData
    ) {
        $this->helper               = $helper;
        $this->converter            = $converter;
        $this->registry             = $registry;
        $this->serializer           = $serializer;
        $this->processDeleteOldData = $processDeleteOldData;
        parent::__construct($resource, $baseHelper, $dataObjectFactory);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'dependency';
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
            'product' => Config::TABLE_NAME,
            'group'   => Config::OPTIONTEMPLATES_TABLE_NAME
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
        $this->entity                      = $entity;
        $this->options                     = $options;
        $this->isAfterTemplate             = $this->entity->getIsAfterTemplate() ?: false;
        $this->isProcessingDependencyRules = $this->entity->getDataObject()->getData(
            'is_processing_dependency_rules'
        ) ?: false;

        $collectedDependencies = $this->collectDependencies();
        if (!$collectedDependencies) {
            return [];
        }

        return $collectedDependencies;
    }

    /**
     * Delete old dependencies
     *
     * @param array $data
     * @return void
     */
    public function deleteOldData(array $data)
    {
        $connection = $this->resource->getConnection();

        if (!isset($this->entity)) {
            return;
        }

        if ($this->entity->getType() == 'group') {
            $groupIds = [];
            foreach ($data as $dataItem) {
                if (!empty($dataItem[Config::COLUMN_NAME_GROUP_ID])
                    && $dataItem[Config::COLUMN_NAME_GROUP_ID]
                    && !in_array($dataItem[Config::COLUMN_NAME_GROUP_ID], $groupIds)
                ) {
                    $groupIds[] = $dataItem[Config::COLUMN_NAME_GROUP_ID];
                }
            }
            if (!$groupIds) {
                return;
            }
            $tableName = $this->resource->getTableName($this->getTableName());

            $connection->delete(
                $tableName,
                [
                    Config::COLUMN_NAME_GROUP_ID . ' IN (?)' => implode(',', $groupIds)
                ]
            );

        } elseif ($this->entity->getType() == 'product') {
            $groupIds   = [];
            $productIds = [];
            foreach ($data as $dataItem) {
                if (!empty($dataItem[Config::COLUMN_NAME_PRODUCT_ID])
                    && $dataItem[Config::COLUMN_NAME_PRODUCT_ID]
                    && !in_array($dataItem[Config::COLUMN_NAME_PRODUCT_ID], $productIds)
                ) {
                    $productIds[] = $dataItem[Config::COLUMN_NAME_PRODUCT_ID];
                }
                if (!empty($dataItem[Config::COLUMN_NAME_GROUP_ID])
                    && $dataItem[Config::COLUMN_NAME_GROUP_ID]
                    && !in_array($dataItem[Config::COLUMN_NAME_GROUP_ID], $groupIds)
                ) {
                    $groupIds[] = $dataItem[Config::COLUMN_NAME_GROUP_ID];
                }
            }
            if (!$productIds) {
                return;
            }

            $tableName = $this->resource->getTableName($this->getTableName());

            if ($this->entity->getDataObject()->getIsAfterTemplateSave()) {
                $isAfterTemplateSave = true;
            } else {
                $isAfterTemplateSave = false;
            }

            $this->processDeleteOldData->deleteOldData(
                $productIds,
                $groupIds,
                'dp_child_option_id',
                $isAfterTemplateSave,
                $tableName
            );
            $this->processDeleteOldData->deleteOldData(
                $productIds,
                $groupIds,
                'dp_parent_option_id',
                $isAfterTemplateSave,
                $tableName
            );

        }
    }

    /**
     * Collect dependencies for future bulk save
     *
     * @return array
     */
    protected function collectDependencies()
    {
        if (empty($this->options)) {
            return [];
        }

        $data = [];
        foreach ($this->options as $option) {
            if (!$this->baseHelper->isSelectableOption($option['type'])) {
                $this->addData($data, $option);
            }

            if (empty($option['values'])) {
                continue;
            }
            foreach ($option['values'] as $value) {
                $this->addData($data, $value);
            }
        }

        if (!$data) {
            return [];
        }

        if (!empty($data['save']) && is_array($data['save'])) {
            $data['save'] = array_unique($data['save'], SORT_REGULAR);
        }

        if (!empty($data['delete']) && is_array($data['delete'])) {
            $data['delete'] = array_unique($data['delete'], SORT_REGULAR);
        }

        return $data;
    }

    /**
     * Add dependencies data from object to overall data array
     *
     * @param $data - option or value.
     * @param $object - option or value.
     * @return void
     */
    protected function addData(&$data, $object)
    {
        $childOptionId     = isset($object['option_id']) ? $object['option_id'] : null;
        $childOptionTypeId = isset($object['option_type_id']) ? $object['option_type_id'] : '';
        $dataObjectId      = $this->entity->getDataObjectId();
        $dependencies      = isset($object['dependency']) ? $object['dependency'] : null;

        // exit if option or value has no dependencies
        if (is_null($dependencies)) {
            return;
        }

        $groupId = null;
        if ($this->entity->getType() == 'product') {
            $groupId          = $this->registry->registry('mageworx_optiontemplates_group_id');
            $data['delete'][] = [
                Config::COLUMN_NAME_PRODUCT_ID => $dataObjectId,
                Config::COLUMN_NAME_GROUP_ID   => $groupId ? $groupId : 0,
            ];
        } else {
            $data['delete'][] = [
                Config::COLUMN_NAME_PRODUCT_ID => 0,
                Config::COLUMN_NAME_GROUP_ID   => $dataObjectId,
            ];
        }

        if (!$dependencies) {
            return;
        }

        $savedDependencies = $this->serializer->unserialize($dependencies);
        if ($this->entity->getType() === 'product'
            && !empty($object['need_to_process_dependency'])
            && !$this->isProcessingDependencyRules
        ) {
            $savedDependencies = $this->convertDependencies($savedDependencies, $dataObjectId);
        }

        // delete non-existent options from dependencies
        $savedDependencies = $this->processDependencies($savedDependencies);
        if (!$this->isAfterTemplate) {
            $savedDependencies = $this->convertRecordIdToId($savedDependencies);
        }

        foreach ($savedDependencies as $dependency) {
            $parentOptionId     = $dependency[0];
            $parentOptionTypeId = $dependency[1];
            if ($this->entity->getType() == 'product') {
                $groupOptionIds = $this->registry->registry('mageworx_optiontemplates_group_option_ids');
                if ($this->shouldSkipSave($groupOptionIds, $object, $groupId)) {
                    continue;
                }

                if (!empty($object['group_id'])) {
                    $groupId = $object['group_id'];
                }

                $data['save'][] = [
                    Config::COLUMN_NAME_DP_CHILD_OPTION_ID       => $childOptionId,
                    Config::COLUMN_NAME_DP_CHILD_OPTION_TYPE_ID  => (int)$childOptionTypeId,
                    Config::COLUMN_NAME_DP_PARENT_OPTION_ID      => $parentOptionId,
                    Config::COLUMN_NAME_DP_PARENT_OPTION_TYPE_ID => $parentOptionTypeId,
                    $this->entity->getDataObjectIdName()         => $dataObjectId,
                    Config::COLUMN_NAME_GROUP_ID                 => $groupId,
                    Config::COLUMN_NAME_IS_PROCESSED_DP_COLUMNS  => '1'
                ];
            } else {
                $data['save'][] = [
                    Config::COLUMN_NAME_DP_CHILD_OPTION_ID       => $childOptionId,
                    Config::COLUMN_NAME_DP_CHILD_OPTION_TYPE_ID  => (int)$childOptionTypeId,
                    Config::COLUMN_NAME_DP_PARENT_OPTION_ID      => $parentOptionId,
                    Config::COLUMN_NAME_DP_PARENT_OPTION_TYPE_ID => $parentOptionTypeId,
                    $this->entity->getDataObjectIdName()         => $dataObjectId,
                    Config::COLUMN_NAME_IS_PROCESSED_DP_COLUMNS  => '1'
                ];
            }
        }

        return;
    }

    /**
     * Check if is needed to skip save dependency
     *
     * @param array $groupOptionIds
     * @param array $object
     * @param int $groupId
     * @return bool
     */
    protected function shouldSkipSave($groupOptionIds, $object, $groupId)
    {
        return $groupOptionIds
            && !$this->isProcessingDependencyRules
            && (!$object['group_option_id']
                || !in_array($object['group_option_id'], $groupOptionIds)
                || (!$groupId && !empty($object['group_id'])));
    }

    /**
     * Convert group dependencies to product ones
     *
     * @param array $savedDependencies
     * @param int $dataObjectId
     * @return array
     */
    protected function convertDependencies($savedDependencies, $dataObjectId)
    {
        //convert magento_id on product
        $this->converter->setData($savedDependencies)
                        ->setProductId($dataObjectId)
                        ->setConvertTo(Converter::CONVERTING_MODE_MAGEWORX)
                        ->setConvertWhere(Converter::CONVERTING_ENTITY_PRODUCT);

        return $this->converter->convert();
    }

    /**
     * Convert group dependencies to product ones
     *
     * @param array $savedDependencies
     * @return array
     */
    protected function processDependencies($savedDependencies)
    {
        $result = [];

        foreach ($savedDependencies as $key => $dependency) {
            if (!$this->isValidDependency($dependency)) {
                continue;
            }
            $result[$key] = $dependency;
        }

        return $result;
    }

    /**
     * Check if dependency is valid
     *
     * @param array $dependency
     * @return bool
     */
    protected function isValidDependency($dependency)
    {
        $isValueMatch  = false;
        $isOptionMatch = false;
        $depOptionId   = (string)$dependency[0];
        $depValueId    = (string)$dependency[1];

        foreach ($this->options as $option) {
            $optionId       = (string)$option['option_id'];
            $optionRecordId = isset($option['record_id']) ? (string)$option['record_id'] : '-1';

            if (!in_array($depOptionId, [$optionId, $optionRecordId])) {
                continue;
            }
            $isOptionMatch = true;

            $values = isset($option['values']) ? $option['values'] : [];
            foreach ($values as $value) {
                $valueId       = (string)$value['option_type_id'];
                $valueRecordId = isset($value['record_id']) ? (string)$value['record_id'] : '-1';

                if (!in_array($depValueId, [$valueId, $valueRecordId])) {
                    continue;
                }
                $isValueMatch = true;
                break 2;
            }
        }

        return $isValueMatch && $isOptionMatch;
    }

    /**
     * Convert recordId to mageworxId
     *
     * @param array $savedDependencies
     * @return array
     */
    protected function convertRecordIdToId($savedDependencies)
    {
        $result = [];

        foreach ($savedDependencies as $key => $dependency) {
            $depOptionId = (string)$dependency[0];
            $depValueId  = (string)$dependency[1];

            foreach ($this->options as $option) {
                $optionId       = (string)$option['option_id'];
                $optionRecordId = isset($option['record_id']) ? (string)$option['record_id'] : '-1';

                if (!in_array($depOptionId, [$optionId, $optionRecordId])) {
                    continue;
                }
                $result[$key][0] = $optionId;

                $values = isset($option['values']) ? $option['values'] : [];
                foreach ($values as $value) {
                    $valueId       = (string)$value['option_type_id'];
                    $valueRecordId = isset($value['record_id']) ? (string)$value['record_id'] : '-1';

                    if (!in_array($depValueId, [$valueId, $valueRecordId])) {
                        continue;
                    }
                    $result[$key][1] = $valueId;
                    break 2;
                }
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function prepareDataForFrontend($object)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function importTemplateMageOne($data)
    {
        if (empty($data['dependency']) || !is_array($data['dependency'])) {
            return '';
        }

        return $this->serializer->serialize($data['dependency']);
    }

    /**
     * {@inheritdoc}
     */
    public function importTemplateMageTwo($data)
    {
        return isset($data[$this->getName()]) ? $data[$this->getName()] : null;
    }
}
