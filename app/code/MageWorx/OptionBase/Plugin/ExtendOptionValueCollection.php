<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionBase\Plugin;

use MageWorx\OptionBase\Model\ResourceModel\CollectionUpdaterFactory;
use MageWorx\OptionBase\Model\ResourceModel\CollectionUpdaterRegistry;
use MageWorx\OptionBase\Model\ResourceModel\Product\ApplyGroupConcat;
use Magento\Catalog\Model\ResourceModel\Product\Option\Value\Collection as ValueCollection;

class ExtendOptionValueCollection
{
    protected CollectionUpdaterFactory $collectionUpdaterFactory;
    protected CollectionUpdaterRegistry $collectionUpdaterRegistry;
    protected ApplyGroupConcat $applyGroupConcat;

    public function __construct(
        CollectionUpdaterFactory $collectionUpdaterFactory,
        CollectionUpdaterRegistry $collectionUpdaterRegistry,
        ApplyGroupConcat $applyGroupConcat
    ) {
        $this->collectionUpdaterFactory  = $collectionUpdaterFactory;
        $this->collectionUpdaterRegistry = $collectionUpdaterRegistry;
        $this->applyGroupConcat          = $applyGroupConcat;
    }

    public function beforeLoad(
        ValueCollection $valueCollection,
        bool $printQuery = false,
        bool $logQuery = false
    ): array {
        if (!$this->collectionUpdaterRegistry->getIsAppliedGroupConcat()) {
            $this->applyGroupConcat->applyGroupConcatMaxLen();
            $this->collectionUpdaterRegistry->setIsAppliedGroupConcat(true);
        }

        if (!$valueCollection->hasFlag('mw_avoid_adding_attributes')) {
            $this->collectionUpdaterFactory->create($valueCollection)->update();
        }

        return [$printQuery, $logQuery];
    }
}
