<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionBase\Model\CollectionUpdater\Option;

use MageWorx\OptionBase\Model\Product\Option\AbstractUpdater;
use MageWorx\OptionBase\Model\OptionPrice;

class Price extends AbstractUpdater
{
    /**
     * {@inheritdoc}
     */
    public function getFromConditions(array $conditions)
    {
        $alias = $this->getTableAlias();
        $table = $this->getTable($conditions);
        return [$alias => $table];
    }

    /**
     * {@inheritdoc}
     */
    public function getTableName($entityType)
    {
        if ($entityType == 'group') {
            return $this->resource->getTableName(OptionPrice::OPTIONTEMPLATES_TABLE_NAME);
        }
        return $this->resource->getTableName(OptionPrice::TABLE_NAME);
    }

    /**
     * {@inheritdoc}
     */
    public function getOnConditionsAsString()
    {
        return 'main_table.' . OptionPrice::FIELD_OPTION_ID . ' = '
            . $this->getTableAlias() . '.' . OptionPrice::FIELD_OPTION_ID_ALIAS;
    }

    /**
     * {@inheritdoc}
     */
    public function getColumns()
    {
        return [
            OptionPrice::KEY_MAGEWORX_OPTION_PRICE =>
                $this->getTableAlias() . '.' . OptionPrice::KEY_MAGEWORX_OPTION_PRICE
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTableAlias()
    {
        return $this->resource->getConnection()->getTableName(OptionPrice::KEY_MAGEWORX_OPTION_PRICE);
    }

    /**
     * Get table for from conditions
     *
     * @param array $conditions
     * @return \Zend_Db_Expr
     */
    private function getTable($conditions)
    {
        $entityType = $conditions['entity_type'];
        $tableName  = $this->getTableName($entityType);

        $selectExpr = "SELECT " . OptionPrice::FIELD_OPTION_ID . " as "
            . OptionPrice::FIELD_OPTION_ID_ALIAS . ","
            . " CONCAT('[',"
            . " GROUP_CONCAT(CONCAT("
            . "'{\"store_id\"',':\"',store_id,'\",',"
            . "'\"price_type\"',':\"',price_type,'\",',"
            . "'\"price\"',':\"',price,'\"}'"
            . ")),"
            . "']')"
            . " AS " . OptionPrice::KEY_MAGEWORX_OPTION_PRICE . " FROM " . $tableName;

        if (!empty($conditions['option_id'])) {
            $selectExpr .= " WHERE option_id IN(" . implode(',', $conditions['option_id']) . ")";
        }
        $selectExpr .= " GROUP BY option_id";

        return new \Zend_Db_Expr('(' . $selectExpr . ')');
    }

    /**
     * {@inheritdoc}
     */
    public function determineJoinNecessity(): bool
    {
        // The mageworx_option_price column is not used in the code, so the join is not necessary
        return false;
    }
}
