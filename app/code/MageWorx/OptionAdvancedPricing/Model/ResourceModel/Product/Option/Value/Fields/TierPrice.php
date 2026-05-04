<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See https://www.mageworx.com/terms-and-conditions for license details.
 */
declare(strict_types = 1);

namespace MageWorx\OptionAdvancedPricing\Model\ResourceModel\Product\Option\Value\Fields;

use Magento\Catalog\Model\ResourceModel\Product\Option\Value\Collection;
use MageWorx\OptionAdvancedPricing\Model\CollectionUpdater\Option\Value\TierPrice as TierPriceCollectionUpdater;
use MageWorx\OptionAdvancedPricing\Model\TierPrice as ModelTierPrice;
use MageWorx\OptionLink\Model\ResourceModel\Product\Option\Value\CollectionUpdater;
use MageWorx\OptionLink\Model\ResourceModel\Product\Option\Value\FieldInterface;

class TierPrice implements FieldInterface
{
    protected TierPriceCollectionUpdater $tierPriceCollectionUpdater;

    public function __construct(
        TierPriceCollectionUpdater $tierPriceCollectionUpdater
    ) {
        $this->tierPriceCollectionUpdater = $tierPriceCollectionUpdater;
    }

    public function addField(Collection $collection): void
    {
        if (!$this->tierPriceCollectionUpdater->determineJoinNecessity()) {
            return;
        }

        $productTableAlias   = CollectionUpdater::KEY_TABLE_OPTIONLINK_PRODUCT;
        $tierPriceTableAlias = $this->tierPriceCollectionUpdater->getTableAlias();

        $collection->getSelect()->columns(
            'IF('
            . 'main_table.sku IS NULL, '
            . $tierPriceTableAlias . '.' . ModelTierPrice::KEY_TIER_PRICE . ', '
            . 'IF(' . $productTableAlias . '.sku IS NULL, '
            . $tierPriceTableAlias . '.' . ModelTierPrice::KEY_TIER_PRICE . ', '
            . 'IF(' . $productTableAlias . '.' . ModelTierPrice::KEY_TIER_PRICE . ' IS NULL, '
            . $tierPriceTableAlias . '.' . ModelTierPrice::KEY_TIER_PRICE . ', '
            . $productTableAlias . '.' . ModelTierPrice::KEY_TIER_PRICE . ')' . ')' .
            ') as ' . ModelTierPrice::KEY_TIER_PRICE
        );
    }
}
