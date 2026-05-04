<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionSkuPolicy\Model\CollectionUpdater\Product;

use MageWorx\OptionBase\Model\Product\AbstractProductUpdater;
use MageWorx\OptionSkuPolicy\Helper\Data as Helper;
use MageWorx\OptionBase\Model\ProductAttributes;

class SkuPolicy extends AbstractProductUpdater
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
            Helper::KEY_SKU_POLICY => $this->getTableAlias() . '.' . Helper::KEY_SKU_POLICY
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
