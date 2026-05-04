<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionBase\Model\ResourceModel\CollectionUpdater;

use Magento\Framework\App\ResourceConnection;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\Profiler;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionBase\Model\Product\CollectionUpdaters as ProductCollectionUpdaters;
use Magento\Catalog\Api\Data\ProductInterface;

class Product
{
    protected ResourceConnection $resource;
    protected ProductCollection $collection;
    protected BaseHelper $baseHelper;
    protected ProductCollectionUpdaters $productCollectionUpdaters;

    public function __construct(
        ResourceConnection $resource,
        ProductCollection $collection,
        BaseHelper $baseHelper,
        ProductCollectionUpdaters $productCollectionUpdaters
    ) {
        $this->resource = $resource;
        $this->collection = $collection;
        $this->baseHelper = $baseHelper;
        $this->productCollectionUpdaters = $productCollectionUpdaters;
    }

    /**
     * Add updaters to collection
     * @return ProductCollection
     * @throws \Zend_Db_Select_Exception
     */
    public function update()
    {
        Profiler::start('APO1: Process PRODUCT collection by updaters');
        $alias = '';
        $productTableName = '';
        $templateTableName = '';
        $attributeKeys = [];
        $partFrom = $this->collection->getSelect()->getPart('from');

        foreach ($this->productCollectionUpdaters->getData() as $productCollectionUpdater) {
            Profiler::start('APO1: process PRODUCT collection updater ' . get_class($productCollectionUpdater));
            $alias = $productCollectionUpdater->getTableAlias();
            $productTableName = $productCollectionUpdater->getProductTableName();
            $templateTableName = $productCollectionUpdater->getTemplateTableName();
            $attributeKeys = array_merge($attributeKeys, $productCollectionUpdater->getColumns());
            Profiler::stop('APO1: process PRODUCT collection updater ' . get_class($productCollectionUpdater));
        }

        if (array_key_exists($alias, $partFrom)
            || empty($productTableName)
            || empty($templateTableName)
            || empty($attributeKeys)
            || empty($alias)
        ) {
            Profiler::stop('APO1: Process PRODUCT collection by updaters');
            return $this->collection;
        }

        if ($partFrom[ProductCollection::MAIN_TABLE_ALIAS]['tableName'] ==
            $this->resource->getTableName($templateTableName)) {
            $tableName = $this->resource->getTableName($templateTableName);
            $condition = '`' . ProductCollection::MAIN_TABLE_ALIAS . '`.`group_id` = `' . $alias . '`.`group_id`';
        } else {
            $tableName = $this->resource->getTableName($productTableName);
            $condition = '`' . ProductCollection::MAIN_TABLE_ALIAS . '`.`' .
                $this->baseHelper->getLinkField(ProductInterface::class) . '` = `' . $alias . '`.`product_id`';
        }

        $this->collection->getSelect()->joinLeft(
            [$alias => $tableName],
            $condition,
            $attributeKeys
        );

        Profiler::stop('APO1: Process PRODUCT collection by updaters');
        return $this->collection;
    }
}
