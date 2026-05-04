<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionFeatures\Model\CollectionUpdater\Option;

use MageWorx\OptionBase\Model\Product\Option\AbstractUpdater;
use MageWorx\OptionFeatures\Model\OptionDescription;

class Description extends AbstractUpdater
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
            return $this->resource->getTableName(OptionDescription::OPTIONTEMPLATES_TABLE_NAME);
        }
        return $this->resource->getTableName(OptionDescription::TABLE_NAME);
    }

    /**
     * {@inheritdoc}
     */
    public function getOnConditionsAsString()
    {
        return 'main_table.' . OptionDescription::COLUMN_NAME_OPTION_ID . ' = '
            . $this->getTableAlias() . '.' . OptionDescription::FIELD_OPTION_ID_ALIAS;
    }

    /**
     * {@inheritdoc}
     */
    public function getColumns()
    {
        return [
            OptionDescription::COLUMN_NAME_DESCRIPTION =>
                $this->getTableAlias() . '.' . OptionDescription::COLUMN_NAME_DESCRIPTION
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTableAlias()
    {
        return $this->resource->getConnection()->getTableName('option_description');
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

        $selectExpr = "SELECT " . OptionDescription::COLUMN_NAME_OPTION_ID . " as "
            . OptionDescription::FIELD_OPTION_ID_ALIAS . ","
            . " CONCAT('[',"
            . " GROUP_CONCAT(CONCAT("
            . "'{\"store_id\"',':\"',IFNULL(store_id,''),'\",',"
            . "'\"description\"',':\"',IFNULL(description,''),'\"}'"
            . ")),"
            . "']')"
            . " AS description FROM " . $tableName;

        if (!empty($conditions['option_id'])) {
            $optionIds = $this->helper->findOptionIdByConditions($conditions);

            if ($optionIds) {
                $selectExpr .= " WHERE option_id IN(" . implode(',', $optionIds) . ")";
            }
        }
        $selectExpr .= " GROUP BY option_id";

        return new \Zend_Db_Expr('(' . $selectExpr . ')');
    }
}
