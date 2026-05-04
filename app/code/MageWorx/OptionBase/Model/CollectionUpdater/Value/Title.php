<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionBase\Model\CollectionUpdater\Value;

use MageWorx\OptionBase\Model\Product\Option\AbstractUpdater;
use MageWorx\OptionBase\Model\OptionTypeTitle;

class Title extends AbstractUpdater
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
            return $this->resource->getTableName(OptionTypeTitle::OPTIONTEMPLATES_TABLE_NAME);
        }
        return $this->resource->getTableName(OptionTypeTitle::TABLE_NAME);
    }

    /**
     * {@inheritdoc}
     */
    public function getOnConditionsAsString()
    {
        return 'main_table.' . OptionTypeTitle::FIELD_OPTION_TYPE_ID . ' = '
            . $this->getTableAlias() . '.' . OptionTypeTitle::FIELD_OPTION_TYPE_ID_ALIAS;
    }

    /**
     * {@inheritdoc}
     */
    public function getColumns()
    {
        return [
            OptionTypeTitle::KEY_MAGEWORX_OPTION_TYPE_TITLE =>
                $this->getTableAlias() . '.' . OptionTypeTitle::KEY_MAGEWORX_OPTION_TYPE_TITLE
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTableAlias()
    {
        return $this->resource->getConnection()->getTableName(OptionTypeTitle::KEY_MAGEWORX_OPTION_TYPE_TITLE);
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

        $selectExpr = "SELECT " . OptionTypeTitle::FIELD_OPTION_TYPE_ID . " as "
            . OptionTypeTitle::FIELD_OPTION_TYPE_ID_ALIAS . ","
            . " CONCAT('[',"
            . " GROUP_CONCAT(CONCAT("
            . "'{\"store_id\"',':\"',store_id,'\",',"
            . "'\"title\"',':\"',REPLACE(title,'\"','&quot;'),'\"}'"
            . ")),"
            . "']')"
            . " AS " . OptionTypeTitle::KEY_MAGEWORX_OPTION_TYPE_TITLE . " FROM " . $tableName;

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
