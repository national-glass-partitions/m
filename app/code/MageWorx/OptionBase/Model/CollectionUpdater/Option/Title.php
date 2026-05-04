<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionBase\Model\CollectionUpdater\Option;

use MageWorx\OptionBase\Model\Product\Option\AbstractUpdater;
use MageWorx\OptionBase\Model\OptionTitle;

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
            return $this->resource->getTableName(OptionTitle::OPTIONTEMPLATES_TABLE_NAME);
        }
        return $this->resource->getTableName(OptionTitle::TABLE_NAME);
    }

    /**
     * {@inheritdoc}
     */
    public function getOnConditionsAsString()
    {
        return 'main_table.' . OptionTitle::FIELD_OPTION_ID . ' = '
            . $this->getTableAlias() . '.' . OptionTitle::FIELD_OPTION_ID_ALIAS;
    }

    /**
     * {@inheritdoc}
     */
    public function getColumns()
    {
        return [
            OptionTitle::KEY_MAGEWORX_OPTION_TITLE =>
                $this->getTableAlias() . '.' . OptionTitle::KEY_MAGEWORX_OPTION_TITLE
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTableAlias()
    {
        return $this->resource->getConnection()->getTableName(OptionTitle::KEY_MAGEWORX_OPTION_TITLE);
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

        $selectExpr = "SELECT " . OptionTitle::FIELD_OPTION_ID . " as "
            . OptionTitle::FIELD_OPTION_ID_ALIAS . ","
            . " CONCAT('[',"
            . " GROUP_CONCAT(CONCAT("
            . "'{\"store_id\"',':\"',store_id,'\",',"
            . "'\"title\"',':\"', REPLACE(title,'\"','&quot;'),'\"}'"
            . ")),"
            . "']')"
            . " AS " . OptionTitle::KEY_MAGEWORX_OPTION_TITLE . " FROM " . $tableName;

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
        if ($this->systemHelper->isAdmin()) {
            return true;
        }

        // Loaded separately
        return false;
    }
}
