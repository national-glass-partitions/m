<?php

namespace MageWorx\OptionDependency\Model;


use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionDependency\Model\ResourceModel\InitialStatesProcessResources as InitialStatesResourceModel;

class InitialStatesProcess
{
    protected BaseHelper $baseHelper;
    protected DependencyRules $dependencyRules;
    protected HiddenDependents $hiddenDependents;
    protected InitialStatesResourceModel $initialStatesResourceModel;

    /**
     * UpgradeSchema constructor.
     *
     * @param BaseHelper $baseHelper
     * @param DependencyRules $dependencyRules
     * @param HiddenDependents $hiddenDependents
     * @param InitialStatesResourceModel $initialStatesResourceModel
     */
    public function __construct(
        BaseHelper $baseHelper,
        DependencyRules $dependencyRules,
        HiddenDependents $hiddenDependents,
        InitialStatesResourceModel $initialStatesResourceModel
    ) {
        $this->baseHelper                 = $baseHelper;
        $this->dependencyRules            = $dependencyRules;
        $this->hiddenDependents           = $hiddenDependents;
        $this->initialStatesResourceModel = $initialStatesResourceModel;

    }

    /**
     * Collect dependency rules and initial state, save to the database
     *
     * @return void
     */
    public function processDependencyRulesUpdate()
    {
        $productDependencyIds = $this->initialStatesResourceModel->getProductIdsSelect();

        if (!$productDependencyIds) {
            return;
        }

        $totalIds = count($productDependencyIds);
        $limit    = 50;

        for ($offset = 0; $offset < $totalIds; $offset += $limit) {
            $ids      = array_slice($productDependencyIds, $offset, $limit);
            $products = [];

            $this->collectValues($products, $ids);
            $this->collectOptions($products, $ids);

            if (!$products) {
                continue;
            }

            $dependenciesPerProducts = $this->getDependencies($ids);
            $this->processProductAttributesData($productAttributesData, $ids);

            $toSave = [];
            $i      = 0;
            foreach ($products as $productId => $productData) {
                $dependencyRules = [];
                if (!empty($dependenciesPerProducts[$productId])) {
                    $dependencies    = $this->dependencyRules->getPreparedDependencies(
                        $dependenciesPerProducts[$productId]
                    );
                    $dependencyRules = $this->dependencyRules->combineRules($dependencies, $productData['options']);
                }

                $hiddenDependents = $this->hiddenDependents->getHiddenDependents(
                    $productData['options'],
                    $dependencyRules
                );

                $productAttributesData[$productId]['product_id']        = $productId;
                $productAttributesData[$productId]['dependency_rules']  = $this->baseHelper->jsonEncode(
                    $dependencyRules
                );
                $productAttributesData[$productId]['hidden_dependents'] = $this->baseHelper->jsonEncode(
                    $hiddenDependents
                );

                $toSave[] = $productAttributesData[$productId];
                $i++;

                if ($i === 10) {
                    $this->initialStatesResourceModel->insertMultipleProductAttributes($toSave);
                    $toSave = [];
                    $i      = 0;
                }
            }

            if ($toSave) {
                $this->initialStatesResourceModel->insertMultipleProductAttributes($toSave);
            }
        }
    }

    /**
     * Collect preselect values, save to the database
     *
     * @return void
     */
    public function processPreselectedValuesUpdate()
    {
        if ($this->initialStatesResourceModel->isMageWorxIsDefaultTableExist()) {
            return;
        }

        $productIsDefaultIds = $this->initialStatesResourceModel->getIsDefaultIdsCollection();
        $productDependencyIds = $this->initialStatesResourceModel->getProductIdsSelect();
        $productIsDefaultOnlyIds = array_diff($productIsDefaultIds, $productDependencyIds);

        if (!$productIsDefaultOnlyIds) {
            return;
        }

        $totalIds = count($productIsDefaultOnlyIds);
        $limit    = 50;

        for ($offset = 0; $offset < $totalIds; $offset += $limit) {
            $ids      = array_slice($productIsDefaultOnlyIds, $offset, $limit);
            $products = [];

            $this->collectIsDefaultValues($products, $ids);

            if (!$products) {
                continue;
            }

            $this->processProductAttributesData($productAttributesData, $ids);

            $toSave = [];
            $i      = 0;
            foreach ($products as $productId => $productData) {
                $isDefaults = $this->getIsDefaults($productData['options']);

                $hiddenDependents = $this->hiddenDependents->getHiddenDependents(
                    $productData['options'],
                    [],
                    $isDefaults
                );

                $productAttributesData[$productId]['product_id']        = $productId;
                $productAttributesData[$productId]['dependency_rules']  = '[]';
                $productAttributesData[$productId]['hidden_dependents'] = $this->baseHelper->jsonEncode(
                    $hiddenDependents
                );

                $toSave[] = $productAttributesData[$productId];
                $i++;

                if ($i === 10) {
                    $this->initialStatesResourceModel->insertMultipleProductAttributes($toSave);
                    $toSave = [];
                    $i      = 0;
                }
            }

            if ($toSave) {
                $this->initialStatesResourceModel->insertMultipleProductAttributes($toSave);
            }
        }

    }

    /**
     * Collect IsDefault values
     *
     * @param $products
     * @param $ids
     */
    protected function collectIsDefaultValues(&$products, $ids)
    {
        if ($this->initialStatesResourceModel->isMageWorxIsDefaultTableExist()) {
            return;
        }

        $fetchedValues = $this->initialStatesResourceModel->getCollectionIsDefaultValues($ids);

        foreach ($fetchedValues as $fetchedValue) {
            $products[$fetchedValue['product_id']]['options'][$fetchedValue['option_id']]['option_id'] = $fetchedValue['option_id'];

            $products[$fetchedValue['product_id']]['options'][$fetchedValue['option_id']]['values'][$fetchedValue['option_type_id']] = [
                'option_type_id'  => $fetchedValue['option_type_id'],
                'is_default'      => $fetchedValue['is_default']
            ];
        }
    }

    /**
     * Collect values during update to dependency rules
     *
     * @param array $products
     * @param array $ids
     * @return void
     */
    protected function collectValues(&$products, $ids)
    {
        $fetchedValues = $this->initialStatesResourceModel->getCollectionValues($ids);

        foreach ($fetchedValues as $fetchedValue) {
            $products[$fetchedValue['product_id']]['options'][$fetchedValue['option_id']]['option_id'] = $fetchedValue['option_id'];
            $products[$fetchedValue['product_id']]['options'][$fetchedValue['option_id']]['type']      = $fetchedValue['type'];

            $products[$fetchedValue['product_id']]['options'][$fetchedValue['option_id']]['values'][$fetchedValue['option_type_id']] = [
                'option_type_id'  => $fetchedValue['option_type_id'],
                'is_default'      => $fetchedValue['is_default'],
                'dependency_type' => $fetchedValue['dependency_type'],
                'type'            => $fetchedValue['type']
            ];
        }
    }

    /**
     * Collect options during update to dependency rules
     *
     * @param array $products
     * @param array $ids
     * @return void
     */
    protected function collectOptions(&$products, $ids)
    {
        $fetchedOptions = $this->initialStatesResourceModel->getCollectionOptions($ids);

        foreach ($fetchedOptions as $fetchedOption) {
            $products[$fetchedOption['product_id']]['options'][$fetchedOption['option_id']] = [
                'option_id'       => $fetchedOption['option_id'],
                'dependency_type' => $fetchedOption['dependency_type'],
                'type'            => $fetchedOption['type']
            ];
        }
    }

    /**
     * Collect dependencies during update to dependency rules
     *
     * @param array $ids
     * @return array
     */
    protected function getDependencies($ids)
    {
        $fetchedDependencies    = $this->initialStatesResourceModel->getCollectionDependencies($ids);
        $dependenciesPerProduct = [];
        foreach ($fetchedDependencies as $fetchedDependency) {
            $dependenciesPerProduct[$fetchedDependency['product_id']][] = $fetchedDependency;
        }
        return $dependenciesPerProduct;
    }

    /**
     * Process product attributes data during update to dependency rules
     *
     * @param array $productAttributesData
     * @param array $ids
     * @return void
     */
    protected function processProductAttributesData(&$productAttributesData, $ids)
    {
        $fetchedProductAttributesData = $this->initialStatesResourceModel->getProductAttributes($ids);

        foreach ($fetchedProductAttributesData as $fetchedProductAttributesDatum) {
            $productAttributesData[$fetchedProductAttributesDatum['product_id']] = $fetchedProductAttributesDatum;
        }
        $this->initialStatesResourceModel->deleteProductAttributes($ids);
    }
}
