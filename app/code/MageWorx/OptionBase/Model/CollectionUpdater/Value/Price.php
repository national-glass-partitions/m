<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionBase\Model\CollectionUpdater\Value;

use MageWorx\OptionBase\Model\Product\Option\AbstractUpdater;
use MageWorx\OptionBase\Model\OptionTypePrice;

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
            return $this->resource->getTableName(OptionTypePrice::OPTIONTEMPLATES_TABLE_NAME);
        }
        return $this->resource->getTableName(OptionTypePrice::TABLE_NAME);
    }

    /**
     * {@inheritdoc}
     */
    public function getOnConditionsAsString()
    {
        return 'main_table.' . OptionTypePrice::FIELD_OPTION_TYPE_ID . ' = '
            . $this->getTableAlias() . '.' . OptionTypePrice::FIELD_OPTION_TYPE_ID_ALIAS;
    }

    /**
     * {@inheritdoc}
     */
    public function getColumns()
    {
        return [
            OptionTypePrice::KEY_MAGEWORX_OPTION_TYPE_PRICE =>
                $this->getTableAlias() . '.' . OptionTypePrice::KEY_MAGEWORX_OPTION_TYPE_PRICE
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTableAlias()
    {
        return $this->resource->getConnection()->getTableName(OptionTypePrice::KEY_MAGEWORX_OPTION_TYPE_PRICE);
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

        $selectExpr = "SELECT " . OptionTypePrice::FIELD_OPTION_TYPE_ID . " as "
            . OptionTypePrice::FIELD_OPTION_TYPE_ID_ALIAS . ","
            . " CONCAT('[',"
            . " GROUP_CONCAT(CONCAT("
            . "'{\"store_id\"',':\"',store_id,'\",',"
            . "'\"price_type\"',':\"',price_type,'\",',"
            . "'\"price\"',':\"',price,'\"}'"
            . ")),"
            . "']')"
            . " AS " . OptionTypePrice::KEY_MAGEWORX_OPTION_TYPE_PRICE . " FROM " . $tableName;

        if (!empty($conditions['option_id']) || !empty($conditions['value_id'])) {
            $ids = $this->helper->findOptionTypeIdByConditions($conditions);

            if ($ids) {
                $selectExpr .= " WHERE option_type_id IN(" . implode(',', $ids) . ")";
            }
        }
        $selectExpr .= " GROUP BY option_type_id";

        return new \Zend_Db_Expr('(' . $selectExpr . ')');
    }
}
