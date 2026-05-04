<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionFeatures\Model\CollectionUpdater\Value\IsDefault;

use MageWorx\OptionBase\Model\Product\Option\AbstractUpdater;
use MageWorx\OptionFeatures\Model\OptionTypeIsDefault;

class IsDefaultStore extends AbstractUpdater
{
    /**
     * {@inheritdoc}
     */
    public function getFromConditions(array $conditions)
    {
        return [$this->getTableAlias() => $this->getTableName($conditions['entity_type'])];
    }

    /**
     * {@inheritdoc}
     */
    public function getTableName($entityType)
    {
        if ($entityType == 'group') {
            return $this->resource->getTableName(OptionTypeIsDefault::OPTIONTEMPLATES_TABLE_NAME);
        }
        return $this->resource->getTableName(OptionTypeIsDefault::TABLE_NAME);
    }

    /**
     * {@inheritdoc}
     */
    public function getOnConditionsAsString()
    {
        $storeId = $this->systemHelper->resolveCurrentStoreId();
        $conditions = 'main_table.option_type_id = ' . $this->getTableAlias() . '.option_type_id';
        $conditions .= " AND " . $this->getTableAlias() . ".store_id = '" . $storeId . "'";
        return $conditions;
    }

    /**
     * {@inheritdoc}
     */
    public function getColumns()
    {
        $isDefaultExpr = $this->resource->getConnection()->getCheckSql(
            'store_option_type_is_default.is_default IS NULL',
            $this->resource->getConnection()->getCheckSql(
                'default_option_type_is_default.is_default IS NULL',
                '0',
                'default_option_type_is_default.is_default'
            ),
            'store_option_type_is_default.is_default'
        );
        return [
            'store_is_default' => $this->getTableAlias() . '.is_default',
            'is_default' => $isDefaultExpr
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTableAlias()
    {
        return 'store_option_type_is_default';
    }
}
