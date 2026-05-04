<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See https://www.mageworx.com/terms-and-conditions for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionAdvancedPricing\Observer;

use Magento\Customer\Api\Data\GroupInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use MageWorx\OptionAdvancedPricing\Model\SpecialPrice;
use MageWorx\OptionAdvancedPricing\Model\TierPrice;
use MageWorx\OptionBase\Helper\Data as HelperBase;

class UpdateFieldsMapObserver implements ObserverInterface
{
    protected \MageWorx\OptionAdvancedPricing\Helper\Data $helper;
    protected HelperBase $helperBase;
    protected ResourceConnection $resource;

    public function __construct(
        \MageWorx\OptionAdvancedPricing\Helper\Data $helper,
        HelperBase $helperBase,
        ResourceConnection $resource
    ) {
        $this->helper     = $helper;
        $this->helperBase = $helperBase;
        $this->resource   = $resource;
    }

    public function execute(Observer $observer)
    {
        /** @var \Magento\Framework\DataObject $object */
        $object    = $observer->getEvent()->getObject();
        $fieldsMap = $object->getFieldsMap() ?: [];

        if (empty($fieldsMap[SpecialPrice::KEY_SPECIAL_PRICE]) && $this->helper->isSpecialPriceEnabled()) {
            $fieldsMap[SpecialPrice::KEY_SPECIAL_PRICE] = [
                'option_name' => SpecialPrice::KEY_SPECIAL_PRICE,
                'type'        => 'attribute',
                'joinType'    => 'left'
            ];
            $fieldsMap['special_from_date']             = [
                'option_name' => 'special_from_date',
                'type'        => 'attribute',
                'joinType'    => 'left'
            ];
            $fieldsMap['special_to_date']               = [
                'option_name' => 'special_to_date',
                'type'        => 'attribute',
                'joinType'    => 'left'
            ];
        }
        if (empty($fieldsMap[TierPrice::KEY_TIER_PRICE]) && $this->helper->isTierPriceEnabled()) {
            $fieldsMap[TierPrice::KEY_TIER_PRICE] = [
                'option_name' => TierPrice::KEY_TIER_PRICE,
                'type'        => 'custom',
                'joinType'    => 'left',
                'table'       => [$this->getTierPriceTableAlias() => $this->getTierPriceTable()],
                'cond'        => $this->getTierPriceConditionsAsString(),
                'cols'        => [
                    TierPrice::KEY_TIER_PRICE => $this->getTierPriceTableAlias() . '.' . TierPrice::KEY_TIER_PRICE
                ]
            ];
        }

        $object->setFieldsMap($fieldsMap);
    }

    protected function getTierPriceTable(): \Zend_Db_Expr
    {
        $tableName       = $this->resource->getTableName('catalog_product_entity_tier_price');
        $entityFieldName = $this->helperBase->isEnterprise() ? 'row_id' : 'entity_id';

        $selectExpr = "SELECT " . $entityFieldName . ","
            . " CONCAT('[',"
            . " GROUP_CONCAT(CONCAT("
            . "'{\"price\"',':\"',IF(value = 0, percentage_value, value),'\",',"
            . "'\"customer_group_id\"',':\"',IF(all_groups > 0, " . GroupInterface::CUST_GROUP_ALL
            . ", customer_group_id),'\",',"
            . "'\"price_type\"',':\"',IF(value = 0,'percentage_discount','fixed'),'\",',"
            . "'\"date_from\"',':\"\",',"
            . "'\"date_to\"',':\"\",',"
            . "'\"qty\"',':\"',IFNULL(qty,''),'\"}'"
            . ")),"
            . "']')"
            . " AS tier_price FROM " . $tableName
            . " WHERE website_id=0"
            . " GROUP BY " . $entityFieldName;

        return new \Zend_Db_Expr('(' . $selectExpr . ')');
    }

    protected function getTierPriceTableAlias(): string
    {
        return 'at_' . TierPrice::KEY_TIER_PRICE;
    }

    protected function getTierPriceConditionsAsString(): string
    {
        $entityFieldName = $this->helperBase->isEnterprise() ? 'row_id' : 'entity_id';

        return 'e.' . $entityFieldName . ' = ' . $this->getTierPriceTableAlias() . '.' . $entityFieldName;
    }
}
