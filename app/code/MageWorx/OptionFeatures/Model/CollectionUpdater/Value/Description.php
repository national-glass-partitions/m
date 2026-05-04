<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionFeatures\Model\CollectionUpdater\Value;

use MageWorx\OptionBase\Model\Product\Option\AbstractUpdater;
use MageWorx\OptionFeatures\Model\OptionTypeDescription;

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
            return $this->resource->getTableName(OptionTypeDescription::OPTIONTEMPLATES_TABLE_NAME);
        }
        return $this->resource->getTableName(OptionTypeDescription::TABLE_NAME);
    }

    /**
     * {@inheritdoc}
     */
    public function getOnConditionsAsString()
    {
        return 'main_table.' . OptionTypeDescription::COLUMN_NAME_OPTION_TYPE_ID . ' = '
            . $this->getTableAlias() . '.' . OptionTypeDescription::FIELD_OPTION_TYPE_ID_ALIAS;
    }

    /**
     * {@inheritdoc}
     */
    public function getColumns()
    {
        return [
            OptionTypeDescription::COLUMN_NAME_DESCRIPTION =>
                $this->getTableAlias() . '.' . OptionTypeDescription::COLUMN_NAME_DESCRIPTION
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTableAlias()
    {
        return $this->resource->getConnection()->getTableName('option_type_description');
    }

    /**
     * Deprecated, using Model/Attribute/OptionValue/Description -> getValueAttributesData()
     * Get table for from conditions
     *
     * @param array $conditions
     * @return \Zend_Db_Expr
     */
    private function getTable($conditions)
    {
        $entityType = $conditions['entity_type'];
        $tableName  = $this->getTableName($entityType);

        $selectExpr = "SELECT " . OptionTypeDescription::COLUMN_NAME_OPTION_TYPE_ID . " as "
            . OptionTypeDescription::FIELD_OPTION_TYPE_ID_ALIAS . ","
            . " CONCAT('[',"
            . " GROUP_CONCAT(CONCAT("
            . "'{\"store_id\"',':\"',IFNULL(store_id,''),'\",',"
            . "'\"description\"',':\"',IFNULL(description,''),'\"}'"
            . ")),"
            . "']')"
            . " AS description FROM " . $tableName;

        if (!empty($conditions['option_id'])) {
            $optionTypeIds = $this->helper->findOptionTypeIdByConditions($conditions);

            if ($optionTypeIds) {
                $selectExpr .= " WHERE option_type_id IN(" . implode(',', $optionTypeIds) . ")";
            }
        }
        $selectExpr .= " GROUP BY option_type_id";

        return new \Zend_Db_Expr('(' . $selectExpr . ')');
    }
}
