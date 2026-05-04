<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See https://www.mageworx.com/terms-and-conditions for license details.
 */
declare(strict_types = 1);

namespace MageWorx\OptionAdvancedPricing\Model\ResourceModel\Product\Option\Value\Fields;

use Magento\Catalog\Model\ResourceModel\Product\Option\Value\Collection;
use MageWorx\OptionAdvancedPricing\Model\CollectionUpdater\Option\Value\SpecialPrice as SpecialPriceCollectionUpdater;
use MageWorx\OptionAdvancedPricing\Model\SpecialPrice as SpecialPriceModel;
use MageWorx\OptionLink\Model\ResourceModel\Product\Option\Value\CollectionUpdater;
use MageWorx\OptionLink\Model\ResourceModel\Product\Option\Value\FieldInterface;

class SpecialPrice implements FieldInterface
{
    protected SpecialPriceCollectionUpdater $specialPriceCollectionUpdater;

    public function __construct(
        SpecialPriceCollectionUpdater $specialPriceCollectionUpdater
    ) {
        $this->specialPriceCollectionUpdater = $specialPriceCollectionUpdater;
    }

    public function addField(Collection $collection): void
    {
        if (!$this->specialPriceCollectionUpdater->determineJoinNecessity()) {
            return;
        }

        $productTableAlias      = CollectionUpdater::KEY_TABLE_OPTIONLINK_PRODUCT;
        $specialPriceTableAlias = $this->specialPriceCollectionUpdater->getTableAlias();
        $specialPriceExpr       =
            " CONCAT('[',"
            . " CONCAT("
            . "'{\"price\"',':\"',IFNULL(" . $productTableAlias . "." . SpecialPriceModel::KEY_SPECIAL_PRICE
            . ",''),'\",',"
            . "'\"customer_group_id\"',':\"',32000,'\",',"
            . "'\"price_type\"',':\"fixed\",',"
            . "'\"date_from\"',':\"',IFNULL(" . $productTableAlias . ".special_from_date,''),'\",',"
            . "'\"date_to\"',':\"',IFNULL(" . $productTableAlias . ".special_to_date,''),'\",',"
            . "'\"comment\"',':\"',IFNULL(" . $specialPriceTableAlias . "." . SpecialPriceModel::FIELD_COMMENT_ALIAS
            . ",''),'\"}'" . ")," . "']')";

        $collection->getSelect()->columns(
            'IF('
            . 'main_table.sku IS NULL, '
            . $specialPriceTableAlias . '.' . SpecialPriceModel::KEY_SPECIAL_PRICE . ', '
            . 'IF(' . $productTableAlias . '.sku IS NULL, '
            . $specialPriceTableAlias . '.' . SpecialPriceModel::KEY_SPECIAL_PRICE . ', '
            . 'IF(' . $productTableAlias . '.' . SpecialPriceModel::KEY_SPECIAL_PRICE . ' IS NULL, '
            . $specialPriceTableAlias . '.' . SpecialPriceModel::KEY_SPECIAL_PRICE . ', '
            . $specialPriceExpr . ')' . ')' .
            ') as ' . SpecialPriceModel::KEY_SPECIAL_PRICE
        );
    }
}
