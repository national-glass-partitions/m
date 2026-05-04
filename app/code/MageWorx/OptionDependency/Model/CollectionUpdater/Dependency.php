<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionDependency\Model\CollectionUpdater;

use MageWorx\OptionBase\Model\Product\AbstractProductUpdater;
use MageWorx\OptionDependency\Model\Config;
use MageWorx\OptionBase\Model\ProductAttributes;

class Dependency extends AbstractProductUpdater
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
            Config::KEY_DEPENDENCY_RULES  => $this->getTableAlias() . '.' . Config::KEY_DEPENDENCY_RULES,
            Config::KEY_HIDDEN_DEPENDENTS => $this->getTableAlias() . '.' . Config::KEY_HIDDEN_DEPENDENTS
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
