<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionDependency\Model\CollectionUpdater\Option;

use Magento\Framework\Exception\LocalizedException;
use MageWorx\OptionBase\Model\Product\Option\AbstractUpdater;
use MageWorx\OptionDependency\Model\Config;

class Dependency extends AbstractUpdater
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
            return $this->resource->getTableName(Config::OPTIONTEMPLATES_TABLE_NAME);
        }
        return $this->resource->getTableName(Config::TABLE_NAME);
    }

    /**
     * {@inheritdoc}
     */
    public function getOnConditionsAsString()
    {
        return $this->getTableAlias().'.dp_child_option_id = main_table.option_id';
    }

    /**
     * {@inheritdoc}
     */
    public function getColumns()
    {
        return ['dependency' => $this->getTableAlias().'.dependency'];
    }

    /**
     * {@inheritdoc}
     */
    public function getTableAlias()
    {
        return 'mageworx_option_dependency';
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
        $tableName = $this->getTableName($entityType);

        $statement = $this->resource->getConnection()->select()
            ->from(
                $tableName,
                [
                    'dp_child_option_id',
                    'dependency' => 'concat(
                            \'[\',
                            group_concat(concat(\'["\', dp_parent_option_id, \'","\', dp_parent_option_type_id, \'"]\')),
                            \']\'
                        )',
                ]
            );

        if (!empty($conditions['entity_ids'])) {
            if ($entityType == 'group') {
                $statement->where(
                    "dp_child_option_type_id = '0' AND group_id "
                    . $this->helper->getComparisonConditionPart($conditions['entity_ids'])
                );
            } else {
                $statement->where(
                    "dp_child_option_type_id = '0' AND product_id " . $this->helper->getComparisonConditionPart(
                        $conditions['row_ids']
                            ? $conditions['row_ids']
                            : $conditions['entity_ids']
                    )
                );
            }
        } else {
            $statement->where('dp_child_option_type_id = ?', '0');
        }

        $statement->group('dp_child_option_id');

        return new \Zend_Db_Expr('(' . $statement->assemble() . ')');
    }

    /**
     * @throws LocalizedException
     */
    public function determineJoinNecessity(): bool
    {
        return !$this->systemHelper->isFrontend();
    }
}
