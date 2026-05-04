<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionBase\Plugin;

use \MageWorx\OptionBase\Model\ResourceModel\CollectionUpdaterFactory;

class ExtendProductCollection
{
    protected CollectionUpdaterFactory $collectionUpdaterFactory;

    /**
     * BeforeLoad constructor.
     *
     * @param CollectionUpdaterFactory $collectionUpdaterFactory
     */
    public function __construct(
        CollectionUpdaterFactory $collectionUpdaterFactory
    ) {
        $this->collectionUpdaterFactory = $collectionUpdaterFactory;
    }

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @param bool $printQuery
     * @param bool $logQuery
     * @return array
     */
    public function beforeLoad($collection, $printQuery = false, $logQuery = false)
    {
        $this->collectionUpdaterFactory->create($collection)->update();

        return [$printQuery, $logQuery];
    }
}
