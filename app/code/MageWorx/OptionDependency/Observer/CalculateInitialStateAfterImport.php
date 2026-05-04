<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionDependency\Observer;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionDependency\Model\DependencyRules;
use MageWorx\OptionDependency\Model\HiddenDependents;

class CalculateInitialStateAfterImport implements ObserverInterface
{
    protected ResourceConnection $resource;
    protected BaseHelper $baseHelper;
    protected DependencyRules $dependencyRules;
    protected HiddenDependents $hiddenDependents;

    public function __construct(
        ResourceConnection $resource,
        BaseHelper $baseHelper,
        DependencyRules $dependencyRules,
        HiddenDependents $hiddenDependents
    ) {
        $this->resource         = $resource;
        $this->baseHelper       = $baseHelper;
        $this->dependencyRules  = $dependencyRules;
        $this->hiddenDependents = $hiddenDependents;
    }

    /**
     * Collect dependency rules and initial state, save to the database after Magento2 product import
     *
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(EventObserver $observer)
    {
        $productIds = $observer->getData('product_ids');
        if (!$productIds) {
            return $this;
        }

        $totalIds = count($productIds);
        $limit    = 50;

        for ($offset = 0; $offset < $totalIds; $offset += $limit) {
            $ids      = array_slice($productIds, $offset, $limit);
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
                    $this->insertMultipleProductAttributes($toSave);
                    $toSave = [];
                    $i      = 0;
                }
            }

            if ($toSave) {
                $this->insertMultipleProductAttributes($toSave);
            }
        }
    }

    /**
     * insertMultiple APO product attributes
     *
     * @param array $data
     * @return void
     */
    protected function insertMultipleProductAttributes($data)
    {
        $this->resource->getConnection()->insertMultiple(
            $this->resource->getTableName('mageworx_optionbase_product_attributes'),
            $data
        );
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
        $valueSelect = $this->resource->getConnection()->select()
                                      ->from(
                                          [
                                              'cpotv' => $this->resource->getTableName(
                                                  'catalog_product_option_type_value'
                                              )
                                          ],
                                          ['option_type_id', 'dependency_type']
                                      )
                                      ->joinLeft(
                                          ['cpo' => $this->resource->getTableName('catalog_product_option')],
                                          "cpo.option_id = cpotv.option_id",
                                          ['option_id', 'product_id', 'type']
                                      );
        if ($this->resource->getConnection()->isTableExists(
            $this->resource->getTableName('mageworx_optionfeatures_option_type_is_default')
        )) {
            $valueSelect->joinLeft(
                ['isdef' => $this->resource->getTableName('mageworx_optionfeatures_option_type_is_default')],
                "isdef.option_type_id = cpotv.option_type_id AND isdef.store_id = 0",
                'is_default'
            );
        }

        $valueSelect->where('cpo.product_id IN (?)', $ids);
        $fetchedValues = $this->resource->getConnection()->fetchAll($valueSelect);

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
        $optionSelect = $this->resource->getConnection()->select()
                                       ->from(
                                           ['cpo' => $this->resource->getTableName('catalog_product_option')],
                                           ['option_id', 'dependency_type', 'product_id', 'type']
                                       )
                                       ->where("cpo.product_id IN (?)", $ids)
                                       ->where("type NOT IN ('drop_down','checkbox','radio','multiple')");

        $fetchedOptions = $this->resource->getConnection()->fetchAll($optionSelect);
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
        $dependencySelect = $this->resource->getConnection()->select()
                                           ->from(
                                               $this->resource->getTableName(
                                                   'mageworx_option_dependency'
                                               ) . ' AS depen',
                                               [
                                                   'dp_child_option_type_id',
                                                   'dp_child_option_id',
                                                   'dp_parent_option_type_id',
                                                   'dp_parent_option_id',
                                                   'product_id'
                                               ]
                                           )
                                           ->where(
                                               "depen.product_id IN (?)",  $ids
                                           );

        $fetchedDependencies    = $this->resource->getConnection()->fetchAll($dependencySelect);
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
        $productAttributesSelect = $this->resource->getConnection()->select()
                                                  ->from(
                                                      $this->resource->getTableName(
                                                          'mageworx_optionbase_product_attributes'
                                                      )
                                                  )
                                                  ->where("product_id IN (?)",  $ids);

        $fetchedProductAttributesData = $this->resource->getConnection()->fetchAll($productAttributesSelect);

        foreach ($fetchedProductAttributesData as $fetchedProductAttributesDatum) {
            $productAttributesData[$fetchedProductAttributesDatum['product_id']] = $fetchedProductAttributesDatum;
        }

        $this->resource->getConnection()->delete(
            $this->resource->getTableName('mageworx_optionbase_product_attributes'),
            ['product_id IN (?)' => $ids]
        );
    }
}
