<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionVisibility\Model\CollectionUpdater\Option;

use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionBase\Model\Product\Option\AbstractUpdater;
use MageWorx\OptionVisibility\Model\OptionStoreView as StoreViewModel;
use Magento\Framework\App\ResourceConnection;
use MageWorx\OptionBase\Helper\Data as Helper;
use MageWorx\OptionBase\Helper\System as SystemHelper;
use MageWorx\OptionVisibility\Helper\Data as VisibilityHelper;
use MageWorx\OptionBase\Helper\CustomerVisibility as CustomerHelper;

class StoreView extends AbstractUpdater
{
    const ALIAS_TABLE_STORE_VIEW = 'option_store_view';

    protected VisibilityHelper $visibilityHelper;
    protected CustomerHelper   $customerHelper;
    protected bool             $isVisibilityFilterRequired;
    protected bool             $isVisibilityStoreView;
    protected BaseHelper       $baseHelper;

    /**
     * CustomerGroup constructor.
     *
     * @param ResourceConnection $resource
     * @param Helper $helper
     * @param SystemHelper $systemHelper
     * @param VisibilityHelper $visibilityHelper
     * @param CustomerHelper $customerHelper
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        ResourceConnection $resource,
        Helper             $helper,
        SystemHelper       $systemHelper,
        VisibilityHelper   $visibilityHelper,
        CustomerHelper     $customerHelper,
        BaseHelper         $baseHelper
    ) {
        $this->visibilityHelper           = $visibilityHelper;
        $this->customerHelper             = $customerHelper;
        $this->isVisibilityFilterRequired = $this->customerHelper->isVisibilityFilterRequired();
        $this->isVisibilityStoreView      = $this->visibilityHelper->isVisibilityStoreViewEnabled();
        $this->baseHelper                 = $baseHelper;

        parent::__construct($resource, $helper, $systemHelper);
    }

    /**
     * {@inheritdoc}
     *
     * @param array $conditions
     * @return array
     */
    public function getFromConditions(array $conditions)
    {
        if ($this->isVisibilityFilterRequired && $this->isVisibilityStoreView) {
            $table = $this->getTableForVisibilityFilter($conditions);
        } else {
            $table = $this->getTable($conditions);
        }
        $alias = $this->getTableAlias();

        return [$alias => $table];
    }

    /**
     * {@inheritdoc}
     *
     * @param string $entityType
     * @return string
     */
    public function getTableName($entityType): string
    {
        if ($entityType == 'group') {
            return $this->resource->getTableName(StoreViewModel::OPTIONTEMPLATES_TABLE_NAME);
        }

        return (string)$this->resource->getTableName(StoreViewModel::TABLE_NAME);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getOnConditionsAsString()
    {
        if ($this->isVisibilityFilterRequired && $this->isVisibilityStoreView) {
            $customerStoreId = $this->customerHelper->getCurrentCustomerStoreId();
            $conditions      = 'main_table.' . StoreViewModel::COLUMN_NAME_OPTION_ID . ' = '
                . $this->getTableAlias() . '.' . StoreViewModel::FIELD_OPTION_ID_ALIAS
                . " AND " . $this->getTableAlias() . "." . StoreViewModel::COLUMN_NAME_STORE_ID
                . " = '" . $customerStoreId . "'";

            return (string)$conditions;
        }

        return 'main_table.' . StoreViewModel::COLUMN_NAME_OPTION_ID . ' = '
            . $this->getTableAlias() . '.' . StoreViewModel::FIELD_OPTION_ID_ALIAS;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getColumns()
    {
        if ($this->isVisibilityFilterRequired && $this->isVisibilityStoreView) {
            $customerStoreExpr = $this->resource->getConnection()->getCheckSql(
                'main_table.is_all_websites = 1',
                '1',
                'IF(' . self::ALIAS_TABLE_STORE_VIEW . '.' . StoreViewModel::COLUMN_NAME_STORE_ID . ' IS NULL,0,1)'
            );

            return [
                'visibility_by_customer_store_id' => $customerStoreExpr
            ];
        }

        return [
            StoreViewModel::KEY_STORE_VIEW => $this->getTableAlias() . '.' . StoreViewModel::KEY_STORE_VIEW
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getTableAlias()
    {
        return (string)$this->resource->getConnection()->getTableName(self::ALIAS_TABLE_STORE_VIEW);
    }

    /**
     * Get table for from conditions
     *
     * @param array $conditions
     * @return \Zend_Db_Expr
     */
    protected function getTable($conditions)
    {
        $entityType = $conditions['entity_type'];
        $tableName  = $this->getTableName($entityType);

        $selectExpr = "SELECT " . StoreViewModel::COLUMN_NAME_OPTION_ID . " as "
            . StoreViewModel::FIELD_OPTION_ID_ALIAS . ","
            . " CONCAT('[',"
            . " GROUP_CONCAT(CONCAT("
            . "'{\"customer_store_id\"',':\"',IFNULL(customer_store_id,''),'\"}'"
            . ")),"
            . "']')"
            . " AS store_view FROM " . $tableName;

        if (!empty($conditions['option_id']) || !empty($conditions['value_id'])) {
            $optionIds = $this->helper->findOptionIdByConditions($conditions);

            if ($optionIds) {
                $selectExpr .= " WHERE option_id IN(" . implode(',', $optionIds) . ")";
            }
        }
        $selectExpr .= " GROUP BY option_id";

        return new \Zend_Db_Expr('(' . $selectExpr . ')');
    }

    /**
     * @param array $conditions
     * @return \Zend_Db_Expr
     */
    protected function getTableForVisibilityFilter($conditions)
    {
        $entityType = $conditions['entity_type'];
        $tableName  = $this->getTableName($entityType);

        $selectExpr = "SELECT " . StoreViewModel::COLUMN_NAME_OPTION_ID . " as "
            . StoreViewModel::FIELD_OPTION_ID_ALIAS . ", "
            . StoreViewModel::COLUMN_NAME_STORE_ID
            . " FROM " . $tableName;

        return new \Zend_Db_Expr('(' . $selectExpr . ')');
    }

    /**
     * {@inheritdoc}
     */
    public function determineJoinNecessity(): bool
    {
        return $this->isVisibilityFilterRequired && $this->isVisibilityStoreView && $this->baseHelper->isEnabledVisibilityPerCustomerStoreView();
    }
}
