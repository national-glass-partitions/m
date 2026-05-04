<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types = 1);

namespace MageWorx\OptionDependency\Model;

use Magento\Framework\App\ResourceConnection;
use MageWorx\OptionBase\Helper\Data as BaseHelper;

class DependencyRules
{
    protected ResourceConnection $resource;
    protected BaseHelper $baseHelper;
    protected array $templateRule = [
        'conditions'     => [],
        'condition_type' => 'or',
        'actions'        => [
            'hide' => [],
        ],
    ];

    protected array $valueWithAndDependencyType = [];

    public function __construct(
        ResourceConnection $resource,
        BaseHelper $baseHelper
    ) {
        $this->resource = $resource;
        $this->baseHelper = $baseHelper;
    }

    /**
     * Combine dependency rules
     *
     * @param array $dependencies
     * @param array $options
     * @return array
     */
    public function combineRules(array $dependencies, array $options)
    {
        $newRules = [];
        $this->valueWithAndDependencyType = [];

        foreach ($options as $option) {
            $isSelectableOption = $this->baseHelper->isSelectableOption($option['type']);
            if (empty($option['values']) && $isSelectableOption) {
                continue;
            }

            /**
             * Add fake value if option does not selectable
             */
            if (!$isSelectableOption) {
                $values = [];
                $values[] = [
                    'option_type_id'            => 'o' . $option['option_id'],
                    Config::KEY_DEPENDENCY_TYPE => $option[Config::KEY_DEPENDENCY_TYPE]
                ];

                if (is_array($option)) {
                    $option['values'] = $values;
                } elseif (is_object($option)) {
                    $option->setValues($values);
                    $option->setData('values', $values);
                }
            }

            $mapOverall = [];
            $mapOrDependencies = [];
            $mapAndDependencies = [];
            foreach ($option['values'] as $optionValue) {
                /**
                 * If an option value is not present in the array, it means that this value has no dependencies
                 * skip it
                 */
                if (empty($dependencies[$optionValue['option_type_id']])) {
                    continue;
                }

                if ($this->isAndDependencyType($optionValue)) {
                    $this->valueWithAndDependencyType[] = (string)$optionValue['option_type_id'];
                }

                foreach ($dependencies[$optionValue['option_type_id']] as $dependency) {
                    if (!is_array($dependency)) {
                        continue;
                    }
                    if ($this->isAndDependencyType($optionValue)) {
                        $this->fillDependencyMapWithoutDuplicates($mapAndDependencies, $dependency);
                    } else {
                        $this->fillDependencyMapWithoutDuplicates($mapOrDependencies, $dependency);
                    }
                    $this->fillDependencyMapWithoutDuplicates($mapOverall, $dependency);
                }
            }

            $this->collectRulesByOrDependencies(
                $newRules,
                $mapOrDependencies,
                (int)$option['option_id']
            );

            $this->collectRulesByAndDependencies(
                $newRules,
                $mapAndDependencies,
                (int)$option['option_id']
            );
        }

        return $this->combineRulesByConditions($newRules);
    }

    /**
     * Get dependencies in specific format
     *
     * @param array $rawDependencies
     * @return array
     */
    public function getPreparedDependencies($rawDependencies): array
    {
        $dependencies = [];
        foreach ($rawDependencies as $rawDependency) {
            if (empty($rawDependency['dp_child_option_type_id'])) {
                $dummyValueId = 'o' . $rawDependency['dp_child_option_id'];
                $rawDependency['dp_child_option_type_id'] = $dummyValueId;
                $dependencies[$dummyValueId][] = $rawDependency;
            } else {
                $dependencies[$rawDependency['dp_child_option_type_id']][] = $rawDependency;
            }
        }

        return $dependencies;
    }

    /**
     * Filling map dependencies, without duplicate values
     *
     * @param array $map
     * @param array $dependency
     * @return void
     */
    protected function fillDependencyMapWithoutDuplicates(array &$map, array $dependency)
    {
        if (!isset($map[$dependency['dp_child_option_type_id']][$dependency['dp_parent_option_id']])
            || !in_array(
                $dependency['dp_parent_option_type_id'],
                $map[$dependency['dp_child_option_type_id']][$dependency['dp_parent_option_id']]
            )
        ) {
            $map[$dependency['dp_child_option_type_id']][$dependency['dp_parent_option_id']][]
                = $dependency['dp_parent_option_type_id'];
        }
    }

    /**
     * Fill rules using OR dependencies
     *
     * @param array $newRules
     * @param array $mapDependencies
     * @param int $optionId
     * @return void
     */
    protected function collectRulesByOrDependencies(
        array &$newRules,
        array $mapDependencies,
        int $optionId
    ) {
        if (!count($mapDependencies)) {
            return;
        }

        foreach ($mapDependencies as $hideValueId => $conditionsOptions) {
            $this->collectRules(
                $newRules,
                $optionId,
                (string)$hideValueId,
                $conditionsOptions
            );
        }
    }

    /**
     * Fill rules using AND dependencies
     *
     * @param array $newRules
     * @param array $mapDependencies
     * @param int $optionId
     * @return void
     */
    protected function collectRulesByAndDependencies(
        array &$newRules,
        array $mapDependencies,
        int $optionId
    ) {
        if (!count($mapDependencies)) {
            return;
        }

        foreach ($mapDependencies as $hideValueId => $conditionsOptions) {
            $this->collectRules(
                $newRules,
                $optionId,
                (string)$hideValueId,
                $conditionsOptions
            );
        }
    }

    /**
     *
     *
     * @param array $newRules
     * @param int $optionId
     * @param string $hideValueId
     * @param mixed $conditionsOptions
     * @return void
     */
    protected function collectRules(array &$newRules, int $optionId, string $hideValueId, $conditionsOptions): void
    {
        if (!is_array($conditionsOptions) || empty($conditionsOptions)) {
            return;
        }

        $isAnd = in_array($hideValueId, $this->valueWithAndDependencyType, true);

        $rule = $this->templateRule;
        foreach ($conditionsOptions as $id => $item) {
            $rule['conditions'][] = [
                'values' => $item,
                'type'   => '!eq',
                'id'     => $id,
            ];
        }

        if ($isAnd) {
            $rule['condition_type'] = 'and';
        }

        if (strstr($hideValueId, 'o')) {
            $rule['actions']['hide'][$optionId] = [
                'values' => [],
                'id'     => $optionId,
            ];
        } else {
            $rule['actions']['hide'][$optionId] = [
                'values' => [
                    $hideValueId => $hideValueId
                ],
                'id'     => $optionId,
            ];
        }

        $newRules[] = $rule;
    }

    /**
     * Collapse rules by same conditions
     *
     * @param array $newRules
     * @return array
     */
    protected function combineRulesByConditions(array $newRules): array
    {
        if (empty($newRules)) {
            return $newRules;
        }
        $resultingRules = [];

        do {
            $ruleElement = array_shift($newRules);

            foreach ($newRules as $ruleId => $newRule) {
                if ($ruleElement['conditions'] !== $newRule['conditions']
                    || $ruleElement['condition_type'] !== $newRule['condition_type']
                ) {
                    continue;
                }
                foreach ($newRule['actions']['hide'] as $hideOptionId => $hideStructure) {
                    if (isset($ruleElement['actions']['hide'][$hideOptionId])) {
                        if (empty($hideStructure['values'])) {
                            continue;
                        }
                        foreach ($hideStructure['values'] as $hideValueId) {
                            if (isset($ruleElement['actions']['hide'][$hideOptionId]['values'][$hideValueId])) {
                                continue;
                            }
                            $ruleElement['actions']['hide'][$hideOptionId]['values'][$hideValueId] = $hideValueId;
                        }
                    } else {
                        $ruleElement['actions']['hide'][$hideOptionId] = $hideStructure;
                    }
                }
                unset($newRules[$ruleId]);
            }

            $resultingRules[] = $ruleElement;

        } while (count($newRules));

        return $resultingRules;
    }

    protected function isAndDependencyType(array $optionValue): bool
    {
        $dependencyType = (int)($optionValue[Config::KEY_DEPENDENCY_TYPE] ?? 0);
        return $dependencyType === 1;
    }
}
