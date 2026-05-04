<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See https://www.mageworx.com/terms-and-conditions for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionDependency\Model\ResourceModel;


use Magento\Framework\App\ResourceConnection;
use MageWorx\OptionDependency\Model\Config as DependencyModel;

class InitialStatesProcessResources
{
    private ResourceConnection $resourceConnection;

    /**
     * ProductPathCollection constructor.
     *
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param $tableName
     * @return bool
     */
    public function isMageWorxIsDefaultTableExist(): bool
    {
        $tableName = $this->resourceConnection->getTableName('mageworx_optionfeatures_option_type_is_default');

        return $this->resourceConnection->getConnection()->isTableExists($tableName);
    }

    public function getIsDefaultIdsCollection(): array
    {

        $productIsDefaultIdsSelect = $this->resourceConnection->getConnection()->select()
                                                              ->distinct()
                                                              ->from(
                                                                  [
                                                                      'cpo' => $this->resourceConnection->getTableName(
                                                                          'catalog_product_option'
                                                                      )
                                                                  ],
                                                                  ['product_id']
                                                              )
                                                              ->join(
                                                                  [
                                                                      'cpotv' => $this->resourceConnection->getTableName(
                                                                          'catalog_product_option_type_value'
                                                                      )
                                                                  ],
                                                                  'cpo.option_id = cpotv.option_id',
                                                                  []
                                                              )
                                                              ->join(
                                                                  [
                                                                      'mcpotid' => $this->resourceConnection->getTableName(
                                                                          'mageworx_optionfeatures_option_type_is_default'
                                                                      )
                                                                  ],
                                                                  'cpotv.option_type_id = mcpotid.option_type_id',
                                                                  []
                                                              )
                                                              ->where('mcpotid.is_default = 1');

        return $this->resourceConnection->getConnection()->fetchCol($productIsDefaultIdsSelect);
    }

    public function getProductIdsSelect(): array
    {
        $productIdsSelect = $this->resourceConnection->getConnection()->select()
                                                     ->distinct()
                                                     ->from(
                                                         $this->resourceConnection->getTableName(
                                                             DependencyModel::TABLE_NAME
                                                         ),
                                                         'product_id'
                                                     );

        return $this->resourceConnection->getConnection()->fetchCol($productIdsSelect);
    }

    /**
     * insertMultiple APO product attributes
     *
     * @param array $data
     * @return void
     */
    public function insertMultipleProductAttributes(array $data): void
    {
        $this->resourceConnection->getConnection()->insertMultiple(
            $this->resourceConnection->getTableName('mageworx_optionbase_product_attributes'),
            $data
        );
    }

    /**
     * Get collection IsDefault Values
     *
     * @param $ids
     * @return array
     */
    public function getCollectionIsDefaultValues(array $ids): array
    {
        $valueSelect = $this->resourceConnection->getConnection()->select()
                                                ->from(
                                                    [
                                                        'mcpotid' => $this->resourceConnection->getTableName(
                                                            'mageworx_optionfeatures_option_type_is_default'
                                                        )
                                                    ],
                                                    ['option_type_id', 'is_default']
                                                )
                                                ->join(
                                                    [
                                                        'cpotv' => $this->resourceConnection->getTableName(
                                                            'catalog_product_option_type_value'
                                                        )
                                                    ],
                                                    'cpotv.option_type_id = mcpotid.option_type_id',
                                                    []
                                                )
                                                ->join(
                                                    [
                                                        'cpo' => $this->resourceConnection->getTableName(
                                                            'catalog_product_option'
                                                        )
                                                    ],
                                                    'cpo.option_id = cpotv.option_id',
                                                    ['option_id', 'product_id']
                                                )
                                                ->where('mcpotid.is_default = 1');
        $valueSelect->where('cpo.product_id IN (?)', $ids);

        return $this->resourceConnection->getConnection()->fetchAll($valueSelect);
    }

    /**
     * Get collection Values
     *
     * @param $ids
     * @return array
     */
    public function getCollectionValues(array $ids): array
    {
        $valueSelect = $this->resourceConnection->getConnection()->select()
                                                ->from(
                                                    [
                                                        'cpotv' => $this->resourceConnection->getTableName(
                                                            'catalog_product_option_type_value'
                                                        )
                                                    ],
                                                    ['option_type_id', 'dependency_type']
                                                )
                                                ->joinLeft(
                                                    [
                                                        'cpo' => $this->resourceConnection->getTableName(
                                                            'catalog_product_option'
                                                        )
                                                    ],
                                                    "cpo.option_id = cpotv.option_id",
                                                    ['option_id', 'product_id', 'type']
                                                );
        if ($this->isMageWorxIsDefaultTableExist()) {
            $valueSelect->joinLeft(
                ['isdef' => $this->resourceConnection->getTableName('mageworx_optionfeatures_option_type_is_default')],
                "isdef.option_type_id = cpotv.option_type_id AND isdef.store_id = 0",
                'is_default'
            );
        }

        $valueSelect->where('cpo.product_id IN (?)', $ids);

        return $this->resourceConnection->getConnection()->fetchAll($valueSelect);
    }

    /**
     * Get collection options
     *
     * @param $products
     * @param $ids
     * @return array
     */
    public function getCollectionOptions(array $ids): array
    {
        $optionSelect = $this->resourceConnection->getConnection()->select()
                                                 ->from(
                                                     [
                                                         'cpo' => $this->resourceConnection->getTableName(
                                                             'catalog_product_option'
                                                         )
                                                     ],
                                                     ['option_id', 'dependency_type', 'product_id', 'type']
                                                 )
                                                 ->where("cpo.product_id IN (?)", $ids)
                                                 ->where("type NOT IN ('drop_down','checkbox','radio','multiple')");

        return $this->resourceConnection->getConnection()->fetchAll($optionSelect);
    }

    /**
     *  Get collection dependencies
     *
     * @param $ids
     * @return array
     */
    public function getCollectionDependencies(array $ids): array
    {
        $dependencySelect = $this->resourceConnection->getConnection()->select()
                                                     ->from(
                                                         $this->resourceConnection->getTableName(
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
                                                     ->where("depen.product_id IN (?)", $ids);

        return $this->resourceConnection->getConnection()->fetchAll($dependencySelect);

    }

    /**
     * Gwt collection product attributes
     *
     * @param $ids
     * @return array
     */
    public function getProductAttributes(array $ids): array
    {
        $productAttributesSelect = $this->resourceConnection->getConnection()->select()
                                                            ->from(
                                                                $this->resourceConnection->getTableName(
                                                                    'mageworx_optionbase_product_attributes'
                                                                )
                                                            )
                                                            ->where("product_id IN (?)", $ids);

        return $this->resourceConnection->getConnection()->fetchAll($productAttributesSelect);

    }

    /**
     * Delete product attributes
     *
     * @param $ids
     */
    public function deleteProductAttributes(array $ids): void
    {
        $this->resourceConnection->getConnection()->delete(
            $this->resourceConnection->getTableName('mageworx_optionbase_product_attributes'),
            $this->resourceConnection->getConnection()->quoteInto('product_id IN(?)', $ids)
        );
    }


}
