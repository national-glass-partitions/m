<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionAdvancedPricing\Model\CollectionUpdater\Option\Value;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use MageWorx\OptionAdvancedPricing\Helper\Data as AdvancedPricingHelper;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionBase\Helper\System as SystemHelper;
use MageWorx\OptionBase\Model\Product\Option\AbstractUpdater;
use MageWorx\OptionAdvancedPricing\Model\SpecialPrice as SpecialPriceModel;

class SpecialPrice extends AbstractUpdater
{
    private AdvancedPricingHelper $advancedPricingHelper;
    private State                 $state;

    public function __construct(
        ResourceConnection    $resource,
        BaseHelper            $helper,
        SystemHelper          $systemHelper,
        AdvancedPricingHelper $advancedPricingHelper,
        State                 $state
    ) {
        parent::__construct($resource, $helper, $systemHelper);
        $this->advancedPricingHelper = $advancedPricingHelper;
        $this->state                 = $state;
    }

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
            return $this->resource->getTableName(SpecialPriceModel::OPTIONTEMPLATES_TABLE_NAME);
        }
        return $this->resource->getTableName(SpecialPriceModel::TABLE_NAME);
    }

    /**
     * {@inheritdoc}
     */
    public function getOnConditionsAsString()
    {
        return 'main_table.' . SpecialPriceModel::COLUMN_OPTION_TYPE_ID . ' = '
            . $this->getTableAlias() . '.' . SpecialPriceModel::FIELD_OPTION_TYPE_ID_ALIAS;
    }

    /**
     * {@inheritdoc}
     */
    public function getColumns()
    {
        return [
            SpecialPriceModel::KEY_SPECIAL_PRICE => $this->getTableAlias() . '.' . SpecialPriceModel::KEY_SPECIAL_PRICE
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTableAlias()
    {
        return $this->resource->getConnection()->getTableName('option_value_special_price');
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

        $selectExpr = "SELECT " . SpecialPriceModel::COLUMN_OPTION_TYPE_ID . " as "
            . SpecialPriceModel::FIELD_OPTION_TYPE_ID_ALIAS . ","
            . SpecialPriceModel::COLUMN_COMMENT . " as " . SpecialPriceModel::FIELD_COMMENT_ALIAS . ","
            . " CONCAT('[',"
            . " GROUP_CONCAT(CONCAT("
            . "'{\"price\"',':\"',IFNULL(price,''),'\",',"
            . "'\"customer_group_id\"',':\"',customer_group_id,'\",',"
            . "'\"price_type\"',':\"',price_type,'\",',"
            . "'\"date_from\"',':\"',IFNULL(date_from,''),'\",',"
            . "'\"date_to\"',':\"',IFNULL(date_to,''),'\",',"
            . "'\"comment\"',':\"',comment,'\"}'"
            . ")),"
            . "']')"
            . " AS special_price FROM " . $tableName;

        if ($conditions && (!empty($conditions['option_id']) || !empty($conditions['value_id']))) {
            $optionTypeIds = $this->helper->findOptionTypeIdByConditions($conditions);

            if ($optionTypeIds) {
                $selectExpr .= " WHERE option_type_id IN(" . implode(',', $optionTypeIds) . ")";
            }
        }
        $selectExpr .= " GROUP BY option_type_id";

        return new \Zend_Db_Expr('(' . $selectExpr . ')');
    }

    /**
     * @throws LocalizedException
     */
    public function determineJoinNecessity(): bool
    {
        /** If the special pricing feature is disabled, you do not need to add this information to the request. */
        if (!$this->advancedPricingHelper->isSpecialPriceEnabled()) {
            return false;
        }

        return !$this->systemHelper->isFrontend();
    }
}
