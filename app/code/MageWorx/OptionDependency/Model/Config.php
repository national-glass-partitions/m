<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionDependency\Model;

use Magento\Catalog\Api\Data\ProductCustomOptionInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Model\AbstractExtensibleModel;

class Config extends AbstractExtensibleModel
{
    const OPTION_TYPE_DROP_DOWN = 'drop_down';
    const OPTION_TYPE_RADIO     = 'radio';
    const OPTION_TYPE_CHECKBOX  = 'checkbox';
    const OPTION_TYPE_MULTIPLE  = 'multiple';

    const TABLE_NAME                 = 'mageworx_option_dependency';
    const OPTIONTEMPLATES_TABLE_NAME = 'mageworx_optiontemplates_group_option_dependency';

    const KEY_DEPENDENCY        = 'dependency';
    const KEY_DEPENDENCY_TYPE   = 'dependency_type';
    const KEY_DEPENDENCY_RULES  = 'dependency_rules';
    const KEY_HIDDEN_DEPENDENTS = 'hidden_dependents';

    const COLUMN_NAME_DEPENDENCY               = 'dependency';
    const COLUMN_NAME_DEPENDENCY_ID            = 'dependency_id';
    const COLUMN_NAME_DP_CHILD_OPTION_ID       = 'dp_child_option_id';
    const COLUMN_NAME_DP_CHILD_OPTION_TYPE_ID  = 'dp_child_option_type_id';
    const COLUMN_NAME_DP_PARENT_OPTION_ID      = 'dp_parent_option_id';
    const COLUMN_NAME_DP_PARENT_OPTION_TYPE_ID = 'dp_parent_option_type_id';
    const COLUMN_NAME_PRODUCT_ID               = 'product_id';
    const COLUMN_NAME_GROUP_ID                 = 'group_id';
    const COLUMN_NAME_IS_PROCESSED_DP_COLUMNS  = 'is_processed_dp_columns';
    const COLUMN_NAME_OPTION_TYPE_TITLE_ID     = 'option_type_title_id';
    const COLUMN_NAME_OPTION_TITLE_ID          = 'option_title_id';
    const COLUMN_NAME_OPTION_DEPENDENCY_TYPE   = 'dependency_type';

    protected array $productOptions = [];
    protected array $optionParents = [];
    protected array $valuesParents = [];
    protected array $optionIdByIds = [];
    protected array $optionTypeIdByIds = [];
    protected array $dependencyOptionsByProduct = [];

    /**
     * Set resource model and Id field name
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('MageWorx\OptionDependency\Model\ResourceModel\Config');
        $this->setIdFieldName('dependency_id');
    }

    /**
     * Get product options
     *
     */
    public function allProductOptions(int $productId): array
    {
        if (!array_key_exists($productId, $this->productOptions)) {
            $this->productOptions[$productId] = $this->_getResource()->allProductOptions($productId);
        }

        return $this->productOptions[$productId];
    }

    /**
     * Get 'dp_child_option_id' - 'dp parent_option_type_id' pairs
     *
     */
    public function getOptionParents(int $productId): array
    {
        if (!array_key_exists($productId, $this->optionParents)) {
            $columns = ['dp_child_option_id', 'dp_parent_option_type_id'];
            $data    = $this->_getResource()
                            ->loadDependencies($productId, $columns);

            $this->optionParents[$productId] = $this->compactArray($data, $columns);
        }

        return $this->optionParents[$productId];
    }

    /**
     * Get 'dp_child_option_type_id' - 'dp_parent_option_type_id' pairs in json
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getValueParents(int $productId): array
    {
        if (!array_key_exists($productId, $this->valuesParents)) {
            $columns                         = ['dp_child_option_type_id', 'dp_parent_option_type_id'];
            $data                            = $this->_getResource()
                                                    ->loadDependencies($productId, $columns);
            $this->valuesParents[$productId] = $this->compactArray($data, $columns);
        }

        return $this->valuesParents[$productId];
    }

    /**
     * Get 'dp_parent_option_type_id' - 'dp_child_option_id' pairs in json
     *
     * @param integer $productId
     * @return array
     */
    public function getOptionChildren($productId)
    {
        $columns = ['dp_parent_option_type_id', 'dp_child_option_id'];
        $data    = $this->_getResource()
                        ->loadDependencies($productId, $columns);

        return $this->compactArray($data, $columns);
    }

    /**
     * Get 'new parent_option_type_id' - 'new child_option_type_id' pairs in json
     *
     * @param integer $productId
     * @return array
     */
    public function getValueChildren($productId)
    {
        $columns = ['dp_parent_option_type_id', 'dp_child_option_type_id'];
        $data    = $this->_getResource()
                        ->loadDependencies($productId, $columns);

        return $this->compactArray($data, $columns);
    }

    /**
     * Get option types ('option_id' => 'type') in json
     *
     * @param integer $productId
     * @return array
     */
    public function getOptionTypes($productId)
    {
        $data = $this->_getResource()
                     ->loadOptionTypes($productId);

        return $data;
    }

    /**
     * Get options with AND-dependency type
     *
     * @param ProductInterface $product
     * @return array
     */
    public function getAndDependencyOptions($product)
    {
        if (!array_key_exists($product->getId(), $this->dependencyOptionsByProduct)) {

            $config = [];
            /** @var \Magento\Catalog\Model\Product\Option[] $options */
            $options = $product->getOptions();
            foreach ($options as $option) {
                if ($option->getDependencyType()) {
                    $config[$option->getData('option_id')] = (bool)$option->getDependencyType();
                }
                if (empty($option->getValues())) {
                    continue;
                }
                /** @var \Magento\Catalog\Model\Product\Option\Value $value */
                foreach ($option->getValues() as $value) {
                    if (is_array($value)) {
                        if (empty($value['option_type_id'])) {
                            continue;
                        }
                        $config[$value['option_type_id']] = (bool)$value['dependency_type'];
                    } elseif ($value->getDependencyType()) {
                        $config[$value->getData('option_type_id')] = (bool)$value->getDependencyType();
                    }
                }
            }

            $this->dependencyOptionsByProduct[$product->getId()] = $config;
        }

        return $this->dependencyOptionsByProduct[$product->getId()];
    }

    /**
     * Retrieve array of mageworx_option_id (mageworx_option_type_id) by option_id (option_type_id)
     *
     * @param string $code
     * @param array $ids
     * @return array
     */
    public function convertToId($code = 'option', $ids = [])
    {
        $key      = hash('sha256', implode('/', $ids));
        $resource = $this->_getResource();

        if ($code == 'option') {
            if (!array_key_exists($key, $this->optionIdByIds)) {
                $this->optionIdByIds[$key] = $resource->loadOptionId($ids);
            }

            return $this->optionIdByIds[$key];
        }

        if (!array_key_exists($key, $this->optionTypeIdByIds)) {
            $this->optionTypeIdByIds[$key] = $resource->loadOptionTypeId($ids);
        }

        return $this->optionTypeIdByIds[$key];
    }

    /**
     * Compact array, remove duplicates
     *
     * @param array $array
     * @param array $cols
     * @return array
     */
    protected function compactArray($array, $cols)
    {
        $keyName   = $cols[0];
        $valueName = $cols[1];

        $result = [];

        foreach ($array as $row) {
            $key   = $row[$keyName];
            $value = $row[$valueName];

            if (!isset($result[$key])) {
                $result[$key][] = $value;
                continue;
            }

            if (in_array($value, $result[$key])) {
                continue;
            }

            $result[$key][] = $value;
        }

        return $result;
    }

    /**
     * Check if it is needed to validate dependent option
     *
     */
    public function isNeedDependentOptionValidation(
        ProductCustomOptionInterface $option,
        array $frontOptions,
        ProductInterface $product,
        int $productId
    ): bool {
        $allProductOptions    = $this->allProductOptions($productId);
        $selectedValues       = $this->convertToId('value', $this->getSelectedValues($frontOptions));
        $optionParents        = $this->getOptionParents($productId);
        $valueParents         = $this->getValueParents($productId);
        $andDependencyOptions = $this->getAndDependencyOptions($product);
        $optionId             = $allProductOptions[$option->getId()];

        if ($this->isSelectableOptionType($option->getType()) && is_null($option->getValues())) {
            return false;
        }

        // 1. If object not exist in parentDependencies then it is not dependent
        // and return true.
        if (!in_array($optionId, array_keys($optionParents))) {
            return true;
        }

        $isDisabledData = $this->prepareIsDisabledData($valueParents);

        // 2. OR dependency: if any of parents are selected - return true
        // AND dependency: if all of parents are selected - return true
        $parentSelected = true;
        if (!empty($option->getValues())) {
            $optionTypeIds = $this->getOptionTypeIds($option);

            $parentSelected       = false;
            $disableRequireOption = false;
            foreach ($valueParents as $childValueId => $parentValueIds) {
                if (!in_array($childValueId, $optionTypeIds)) {
                    continue;
                }

                if (in_array($childValueId, array_keys($andDependencyOptions))) {
                    $parentSelected = true;
                    foreach ($parentValueIds as $parentValueId) {
                        if (!in_array($parentValueId, $selectedValues)) {
                            $parentSelected = false;
                            break;
                        }

                        if ($this->isDisabledParentOption($isDisabledData, $parentValueId)) {
                            $disableRequireOption = true;
                        }
                    }
                } else {
                    foreach ($parentValueIds as $parentValueId) {
                        if (in_array($parentValueId, $selectedValues)) {
                            $parentSelected = true;

                            if ($this->isDisabledParentOption($isDisabledData, $parentValueId)) {
                                $disableRequireOption = true;
                            }
                            break;
                        }
                    }
                }
                if ($parentSelected) {
                    if ($disableRequireOption) {
                        return false;
                    }

                    return true;
                }
            }
        } elseif (!$this->isSelectableOptionType($option->getType())) {
            $parentSelected = false;
            $parents        = $optionParents[$optionId];
            if (in_array($optionId, array_keys($andDependencyOptions))) {
                $parentSelected = true;
                foreach ($parents as $parentValueId) {
                    if (!in_array($parentValueId, $selectedValues)) {
                        $parentSelected = false;
                        break;
                    }
                }
            } else {
                foreach ($parents as $parentValueId) {
                    if (in_array($parentValueId, $selectedValues)) {
                        $parentSelected = true;
                        break;
                    }
                }
            }
        }

        // if option is required and hidden (parent value not selected) - set IsRequire = false
        if (!$parentSelected) {
            return false;
        }

        return true;
    }

    /**
     * @param array $valueParents
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function prepareIsDisabledData($valueParents)
    {
        $ids = [];

        foreach ($valueParents as $childValueId => $parentValueIds) {
            foreach ($parentValueIds as $parentValueId) {
                $ids[] = $parentValueId;
            }
        }

        $ids      = array_unique($ids);
        $resource = $this->_getResource();
        $data     = $resource->getIsDisabledData($ids);

        return $data;
    }

    /**
     *
     * @param array $prepareData
     * @param int $parentValueId
     * @return bool
     */
    protected function isDisabledParentOption($prepareData, $parentValueId)
    {
        foreach ($prepareData as $value) {
            if ($value['dp_parent_option_type_id'] == $parentValueId) {
                return (bool)$value['disabled'];
            }
        }

        return false;
    }

    /**
     * Get selected values
     *
     * @param array|null $frontOptions
     * @return array
     */
    protected function getSelectedValues($frontOptions)
    {
        $result = [];

        if (!is_array($frontOptions) || !$frontOptions) {
            return $result;
        }

        foreach ($frontOptions as $optionId => $values) {
            if (!is_array($values) && !is_numeric($values)) {
                continue;
            }

            if (is_numeric($values)) {
                $values = [$values];
            }

            /* Order Editor provides extra data from buyRequest when editing an order
               url - the parameter is not important for us
            */
            if (isset($values['url'])) {
                unset($values['url']);
            }

            $result = array_merge($result, $values);
        }

        return $result;
    }

    /**
     * Check if option has selectable type
     *
     * @param string $optionType
     * @return bool
     */
    public function isSelectableOptionType($optionType)
    {
        if ($optionType == self::OPTION_TYPE_CHECKBOX
            || $optionType == self::OPTION_TYPE_DROP_DOWN
            || $optionType == self::OPTION_TYPE_RADIO
            || $optionType == self::OPTION_TYPE_MULTIPLE
        ) {
            return true;
        }

        return false;
    }

    /**
     * Get option ids
     */
    public function getOptionTypeIds(ProductCustomOptionInterface $option): array
    {
        $optionTypeIds = [];
        foreach ($option->getValues() as $optionValue) {
            if (is_array($optionValue)) {
                if (!empty($value['option_type_id'])) {
                    $optionTypeIds[] = $value['option_type_id'];
                }
            } else {
                $optionTypeIds[] = $optionValue->getOptionTypeId();
            }
        }

        return $optionTypeIds;
    }
}
