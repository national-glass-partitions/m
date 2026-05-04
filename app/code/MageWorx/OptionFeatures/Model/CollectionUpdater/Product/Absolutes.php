<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionFeatures\Model\CollectionUpdater\Product;

use MageWorx\OptionBase\Model\Product\AbstractProductUpdater;
use MageWorx\OptionFeatures\Helper\Data as Helper;
use MageWorx\OptionBase\Model\ProductAttributes;

class Absolutes extends AbstractProductUpdater
{
    /**
     * {@inheritdoc}
     */
    public function getProductTableName()
    {
        return $this->resource->getTableName(ProductAttributes::TABLE_NAME);
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplateTableName()
    {
        return $this->resource->getTableName(ProductAttributes::OPTIONTEMPLATES_TABLE_NAME);
    }

    /**
     * {@inheritdoc}
     */
    public function getColumns()
    {
        return [
            Helper::KEY_ABSOLUTE_PRICE => $this->getTableAlias() . '.' . Helper::KEY_ABSOLUTE_PRICE,
            Helper::KEY_ABSOLUTE_COST => $this->getTableAlias() . '.' . Helper::KEY_ABSOLUTE_COST,
            Helper::KEY_ABSOLUTE_WEIGHT => $this->getTableAlias() . '.' . Helper::KEY_ABSOLUTE_WEIGHT
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTableAlias()
    {
        return 'product_attributes';
    }
}
