<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types = 1);

namespace MageWorx\OptionDependency\Model;

use Magento\Framework\DataObject;
use Magento\Catalog\Api\Data\ProductCustomOptionInterface;

use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionBase\Model\HiddenDependents as HiddenDependentsStorage;

class HiddenDependents
{
    protected BaseHelper $baseHelper;
    protected HiddenDependentsStorage $hiddenDependentsStorage;
    protected array $data;
    protected array $hiddenValues;
    protected array $hiddenOptions;
    protected array $selectedValues;
    protected array $optionToValuesMap;
    protected array $valueToOptionMap;
    protected array $dependencyRules;
    protected bool $hasConfigureQuoteItemsHiddenDependents = false;

    public function __construct(
        BaseHelper $baseHelper,
        HiddenDependentsStorage $hiddenDependentsStorage
    ) {
        $this->baseHelper = $baseHelper;
        $this->hiddenDependentsStorage = $hiddenDependentsStorage;
    }

    /**
     * Get hidden dependents
     *
     * @used to hide options/values on their backend rendering
     *
     * @param array $options
     * @param array $dependencyRules
     * @return array
     */
    public function getHiddenDependents($options, $dependencyRules)
    {
        $this->data = [
            'hidden_options'     => [],
            'hidden_values'      => [],
            'preselected_values' => []
        ];

        if (!$options || !is_array($options)) {
            return $this->data;
        }

        $this->selectedValues = [];
        $this->optionToValuesMap = [];
        $this->valueToOptionMap = [];
        $this->dependencyRules = $dependencyRules;

        $this->collectOptionToValuesMap($options);
        $this->processDependencyRules();

        $isDefaults = $this->getIsDefaults($options);
        $this->processIsDefaults($isDefaults, $options);

        $this->data = [
            'hidden_options'     => array_values($this->hiddenOptions),
            'hidden_values'      => array_values($this->hiddenValues),
            'preselected_values' => $this->getPreparedSelectedValues()
        ];

        return $this->data;
    }

    /**
     * Calculate hidden dependents for ShareableLink or GraphQL's "dependencyState" query
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param array $preselectedValues
     *
     * @return void
     */
    public function calculateHiddenDependents($product, $preselectedValues = []): void
    {
        if (
            !is_a($product, \Magento\Catalog\Api\Data\ProductInterface::class) ||
            !is_array($preselectedValues)
        ) {
            return;
        }

        $this->data = [
            'hidden_options'     => [],
            'hidden_values'      => [],
            'preselected_values' => []
        ];

        $options = $this->getOptionsAsArray($product->getOptions());
        if (empty($options)) {
            return;
        }

        $dependencyRulesJson = $product->getDependencyRules();
        try {
            $dependencyRules = $this->baseHelper->jsonDecode($dependencyRulesJson);
        } catch (\Exception $exception) {
            $dependencyRules = [];
        }

        $this->optionToValuesMap = [];
        $this->valueToOptionMap = [];
        $this->dependencyRules = $dependencyRules;
        $this->selectedValues = $preselectedValues;

        $this->collectOptionToValuesMap($options);
        $this->processDependencyRules();

        $isDefaults = $this->getIsDefaults($options, $preselectedValues);
        $this->processIsDefaults($isDefaults, $options);

        $this->data = [
            'hidden_options'     => array_values($this->hiddenOptions),
            'hidden_values'      => array_values($this->hiddenValues),
            'preselected_values' => $this->getPreparedSelectedValues()
        ];

        $this->hasConfigureQuoteItemsHiddenDependents = true;
        $this->hiddenDependentsStorage->setQuoteItemsHiddenDependents($this->data);
    }

    /**
     * Calculate configure quote items hidden dependents
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param DataObject $buyRequest
     *
     * @return void
     */
    public function calculateConfigureQuoteItemsHiddenDependents($product, $buyRequest): void
    {
        if (
            $this->hasConfigureQuoteItemsHiddenDependents ||
            !is_a($product, \Magento\Catalog\Api\Data\ProductInterface::class) ||
            !is_a($buyRequest, DataObject::class)
        ) {
            return;
        }

        $this->data = [
            'hidden_options'     => [],
            'hidden_values'      => [],
            'preselected_values' => []
        ];

        $options = $this->getOptionsAsArray($product->getOptions());
        if (empty($options)) {
            return;
        }

        $dependencyRulesJson = $product->getDependencyRules();
        try {
            $dependencyRules = $this->baseHelper->jsonDecode($dependencyRulesJson);
        } catch (\Exception $exception) {
            $dependencyRules = [];
        }

        $this->selectedValues = [];
        $this->optionToValuesMap = [];
        $this->valueToOptionMap = [];
        $this->dependencyRules = $dependencyRules;

        $this->collectSelectedValuesFromBuyRequest($buyRequest);
        $this->collectOptionToValuesMap($options);
        $this->processDependencyRules();

        $this->data = [
            'hidden_options'     => array_values($this->hiddenOptions),
            'hidden_values'      => array_values($this->hiddenValues),
            'preselected_values' => $this->getPreparedSelectedValues()
        ];

        $this->hasConfigureQuoteItemsHiddenDependents = true;
        $this->hiddenDependentsStorage->setQuoteItemsHiddenDependents($this->data);
    }

    /**
     * Get value to option map from option's array
     *
     * @param array $options
     * @return array
     */
    public function getValueOptionMaps($options)
    {
        $this->optionToValuesMap = [];
        $this->valueToOptionMap = [];
        if (is_array($options)) {
            $this->collectOptionToValuesMap($options);
        }
        return [
            'valueToOption' => $this->valueToOptionMap,
            'optionToValue' => $this->optionToValuesMap
        ];
    }

    /**
     * Convert option's objects to array and retrieve it.
     *
     * @param array|null $options
     * @return array
     */
    public function getOptionsAsArray(?array $options): array
    {
        if (!$options) {
            $options = [];
        }

        $results = [];

        foreach ($options as $option) {
            if (!is_object($option)) {
                continue;
            }
            $result = [];
            $result['option_id'] = $option->getOptionId();
            $result['type'] = $option->getType();
            $result['is_hidden'] = $option->getData('is_hidden');

            if ($this->baseHelper->isSelectableOption($option->getType()) && $option->getValues()) {
                foreach ($option->getValues() as $value) {
                    $i = $value->getOptionTypeId();
                    foreach ($value->getData() as $valueKey => $valueDatum) {
                        $result['values'][$i][$valueKey] = $valueDatum;
                    }
                }
            } else {
                foreach ($option->getData() as $optionKey => $optionDatum) {
                    $result[$optionKey] = $optionDatum;
                }
                $result['values'] = null;
            }

            $results[$option->getOptionId()] = $result;
        }

        return $results;
    }

    /**
     * Getter for HiddenDependents::hasConfigureQuoteItemsHiddenDependents
     *
     * @return array
     */
    public function getConfigureQuoteItemsHiddenDependents(): array
    {
        if ($this->hasConfigureQuoteItemsHiddenDependents) {
            return $this->data;
        }

        return [];
    }

    /**
     * Collect selected values from quote item's buyRequest
     *
     * @param DataObject $buyRequest
     * @return void
     */
    protected function collectSelectedValuesFromBuyRequest(DataObject $buyRequest): void
    {
        $selectedOptions = $buyRequest->getData('options');
        if (!$selectedOptions || !is_array($selectedOptions)) {
            return;
        }
        foreach ($selectedOptions as $selectedValues) {
            if (empty($selectedValues)) {
                continue;
            }
            if (is_array($selectedValues)) {
                foreach ($selectedValues as $selectedValue) {
                    $this->selectedValues[] = $selectedValue;
                }
            } else {
                $this->selectedValues[] = $selectedValues;
            }
        }
    }

    /**
     * Get product collection using selected product IDs
     *
     * @param array $options
     * @return array
     */
    protected function getIsDefaults(array $options, array $preselectedValues = []): array
    {
        $isDefaults = [];
        foreach ($options as $option) {
            if (empty($option['values'])) {
                continue;
            }

            /**
             * Only one value can be selected for radio or drop down type options
             */
            if(
                !empty($option['type']) &&
                $this->isOneChoiceOptionType($option['type']) &&
                $this->isThereConflict($option['values'], $preselectedValues)
            ) {
                continue;
            }

            foreach ($option['values'] as $value) {
                if (empty($value['is_default'])) {
                    continue;
                }
                $isDefaults[] = [
                    'option_type_id' => $value['option_type_id'],
                    'is_default'     => $value['is_default']
                ];
            }
        }
        return $isDefaults;
    }

    /**
     * Checks for conflict between selected values and default values
     * @return void
     */
    protected function isThereConflict(array $values, array $preselectedValues): bool
    {
        if(empty($preselectedValues)) {
            return false;
        }
        foreach ($values as $value) {
            if(isset($value['option_type_id']) && in_array($value['option_type_id'], $preselectedValues)) {
                return true;
            }
        }
        return false;
    }

    /**
     * It collects two arrays:
     * optionToValue: here the option ids as keys, and the value of the array is an array of ids of the values of that option
     * valueToOption: In this one it is the other way round - each option id value corresponds an option id
     *
     * @param array $options
     * @return void
     */
    protected function collectOptionToValuesMap(array $options): void
    {
        foreach ($options as $option) {
            if (!isset($option['option_id'])
                || empty($option['values'])
                || !empty($option['is_disabled'])
            ) {
                continue;
            }
            $valueIds = [];
            foreach ($option['values'] as $value) {
                if (!isset($value['option_type_id'])
                    || !empty($value['is_disabled'])
                ) {
                    continue;
                }
                $valueIds[] = $value['option_type_id'];
                $this->valueToOptionMap[$value['option_type_id']] = $option['option_id'];
            }
            $this->optionToValuesMap[$option['option_id']] = $valueIds;
        }
    }

    /**
     * Process IsDefaults, rerun dependency rules and isDefaults check, if new selected value is added
     *
     * @param array $isDefaults
     * @param array $options
     * @return void
     */
    protected function processIsDefaults(array $isDefaults, array $options): void
    {
        if (empty($isDefaults)) {
            return;
        }

        $isAddedNewSelectedValue = false;
        foreach ($isDefaults as $isDefault) {
            if (isset($isDefault['option_type_id'])) {
                $optionTypeId = (int)$isDefault['option_type_id'];
                foreach ($options as $option) {

                    if (isset($option['is_hidden']) && (int)$option['is_hidden'] !== 0) {
                        continue;
                    }

                    if (!empty($option['values'])) {
                        foreach ($option['values'] as $value) {
                            if ((int)$value['option_type_id'] == $optionTypeId) {
                                if (
                                    (!isset($value['qty']) || (int)$value['qty'] === 0) &&
                                    isset($value['manage_stock']) && (int)$value['manage_stock'] !== 0
                                ) {
                                    $this->hiddenValues[$optionTypeId] = $optionTypeId;
                                }
                            }
                        }
                    }
                }

                if ($this->isCanNotBePreselectedValue($isDefault, $optionTypeId)) {
                    continue;
                }
                $this->selectedValues[] = $isDefault['option_type_id'];

                $isAddedNewSelectedValue = true;
            }
        }

        if ($isAddedNewSelectedValue) {
            $this->processDependencyRules();
            $this->processIsDefaults($isDefaults, $options);
        }
    }

    protected function isOneChoiceOptionType(string $type): bool
    {
        return in_array($type, [ProductCustomOptionInterface::OPTION_TYPE_RADIO, ProductCustomOptionInterface::OPTION_TYPE_DROP_DOWN]);
    }

    protected function processDependencyRules(): void
    {
        $this->hiddenValues = [];
        $this->hiddenOptions = [];
        foreach ($this->dependencyRules as $dependencyRule) {
            if (
                !is_array($dependencyRule) ||
                !isset($dependencyRule['condition_type'], $dependencyRule['conditions'], $dependencyRule['actions'])
            ) {
                continue;
            }

            if ($dependencyRule['condition_type'] === 'and') {
                $this->processDependencyAndRules($dependencyRule);
            } else {
                $this->processDependencyOrRules($dependencyRule);
            }
        }

        $this->hideOptionIfAllValuesHidden();
    }

    protected function processDependencyOrRules(array $dependencyRule): void
    {

        $conditionsMet =
            !empty($dependencyRule['conditions']); //If there are no conditions, you do not need to perform the action
        foreach ($dependencyRule['conditions'] as $item) {
            $conditionOptionValues = $item['values'];

            if (empty($conditionOptionValues) && !empty($item['id']) && !empty($this->optionToValuesMap[$item['id']])) {
                $conditionOptionValues = $this->optionToValuesMap[$item['id']];
            }

            if ($item['type'] === '!eq') {
                /**
                 * value in selected != hidden
                 */
                foreach ($conditionOptionValues as $conditionOptionValueId) {
                    if (in_array($conditionOptionValueId, $this->selectedValues)) {
                        $conditionsMet = false;
                        break 2;
                    }
                }
            } elseif ($item['type'] === 'eq') {
                /**
                 * value in selected = hidden
                 *
                 * We don't have equality conditions in use at the moment, for this reason I have removed it
                 *
                 * However, when we use it we have to take into account that if at least 1 value is selected,
                 * then we have to perform the hidden action, but if no element is selected, then the action is not performed.
                 */
            }
        }

        if ($conditionsMet) {
            $this->addHiddenValuesByRule($dependencyRule);
        }
    }

    protected function processDependencyAndRules(array $dependencyRule): void
    {
        $conditionsMet = false;
        foreach ($dependencyRule['conditions'] as $item) {
            $conditionOptionValues = $item['values'];

            if (empty($conditionOptionValues) && !empty($item['id']) && !empty($this->optionToValuesMap[$item['id']])) {
                $conditionOptionValues = $this->optionToValuesMap[$item['id']];
            }

            if ($item['type'] === '!eq') {
                /**
                 * value !in selected = hidden
                 */
                foreach ($conditionOptionValues as $conditionOptionValueId) {
                    if (!in_array($conditionOptionValueId, $this->selectedValues)) {
                        $conditionsMet = true;
                        break 2;
                    }
                }
            } elseif ($item['type'] === 'eq') {
                /**
                 * value !in selected != hidden
                 *
                 * We don't have equality conditions in use at the moment, for this reason I have removed it
                 *
                 * In this case, in order to perform the action,
                 * all option values from the $conditionOptionValues array must be in $this->selectedValues
                 */
            }
        }

        if ($conditionsMet) {
            $this->addHiddenValuesByRule($dependencyRule);
        }
    }

    protected function addHiddenValuesByRule(array $dependencyRule): void
    {
        foreach ($dependencyRule['actions']['hide'] as $hideItem) {
            if (!empty($hideItem['values']) && is_array($hideItem['values'])) {
                foreach ($hideItem['values'] as $hideValueId) {
                    if ($hideValueId) {
                        $this->hiddenValues[(int)$hideValueId] = (int)$hideValueId;
                    }
                }
            } else {

                if (empty($hideItem['id'])) {
                    continue;
                }

                $this->hiddenOptions[(int)$hideItem['id']] = (int)$hideItem['id'];

                $optionValues = $this->optionToValuesMap[$hideItem['id']] ?? [];
                foreach ($optionValues as $hideValueId) {
                    if ($hideValueId) {
                        $this->hiddenValues[(int)$hideValueId] = (int)$hideValueId;
                    }
                }
            }
        }
    }

    protected function hideOptionIfAllValuesHidden(): void
    {
        foreach ($this->optionToValuesMap as $optionId => $valueIds) {
            if (!$valueIds) {
                continue;
            }
            $areAllValuesHidden = true;
            foreach ($valueIds as $valueId) {
                if (!in_array($valueId, $this->hiddenValues) || substr((string)$valueId, 0, 1) === 'o') {
                    $areAllValuesHidden = false;
                    break;
                }
            }
            if ($areAllValuesHidden) {
                $this->hiddenOptions[(int)$optionId] = (int)$optionId;
            }
        }
    }

    protected function isCanNotBePreselectedValue(array $isDefault, int $optionTypeId): bool
    {
        return empty($isDefault['is_default']) ||
            in_array($isDefault['option_type_id'], $this->selectedValues) ||
            in_array($optionTypeId, $this->hiddenValues) ||
            empty($this->valueToOptionMap[$isDefault['option_type_id']]);
    }

    protected function getPreparedSelectedValues(): array
    {
        $data = [];
        foreach ($this->selectedValues as $selectedValue) {
            if (!isset($this->valueToOptionMap[$selectedValue])) {
                continue;
            }
            $data[$this->valueToOptionMap[$selectedValue]][] = (int)$selectedValue;
        }

        return $data;
    }
}
