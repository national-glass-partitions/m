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
use Magento\Catalog\Model\ResourceModel\Product\Option\Collection as OptionCollection;

class ExtendOptionCollection
{
    protected CollectionUpdaterFactory $collectionUpdaterFactory;
    protected CollectionUpdaterRegistry $collectionUpdaterRegistry;
    protected ApplyGroupConcat $applyGroupConcat;

    /**
     * BeforeLoad constructor.
     *
     * @param CollectionUpdaterFactory $collectionUpdaterFactory
     * @param CollectionUpdaterRegistry $collectionUpdaterRegistry
     * @param ApplyGroupConcat $applyGroupConcat
     */
    public function __construct(
        CollectionUpdaterFactory $collectionUpdaterFactory,
        CollectionUpdaterRegistry $collectionUpdaterRegistry,
        ApplyGroupConcat $applyGroupConcat
    ) {
        $this->collectionUpdaterFactory = $collectionUpdaterFactory;
        $this->collectionUpdaterRegistry = $collectionUpdaterRegistry;
        $this->applyGroupConcat = $applyGroupConcat;
    }

    public function beforeLoad(
        OptionCollection $optionCollection,
        bool $printQuery = false,
        bool $logQuery = false
    ): array {
        if (!$this->collectionUpdaterRegistry->getIsAppliedGroupConcat()) {
            $this->applyGroupConcat->applyGroupConcatMaxLen();
            $this->collectionUpdaterRegistry->setIsAppliedGroupConcat(true);
        }

        if (!$optionCollection->hasFlag('mw_avoid_adding_options_attributes')) {
            $this->collectionUpdaterFactory->create($optionCollection)->update();
        }

        return [$printQuery, $logQuery];
    }
}
