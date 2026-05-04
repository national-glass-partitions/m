<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionBase\Model\ResourceModel\CollectionUpdater;

use Magento\Catalog\Model\ResourceModel\Product\Option\Value\Collection;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Profiler;
use MageWorx\OptionBase\Helper\CustomerVisibility;
use MageWorx\OptionBase\Helper\Data;
use MageWorx\OptionBase\Model\Product\Option\AbstractUpdater;
use MageWorx\OptionBase\Model\Product\Option\CollectionUpdaters as OptionCollectionUpdaters;
use MageWorx\OptionBase\Model\Product\Option\Value\CollectionUpdaters as ValueCollectionUpdaters;
use MageWorx\OptionBase\Model\ResourceModel\CollectionUpdaterAbstract;
use MageWorx\OptionBase\Model\ResourceModel\CollectionUpdaterRegistry;

class Value extends CollectionUpdaterAbstract
{
    protected AdapterInterface   $connection;
    protected CustomerVisibility $helperCustomerVisibility;
    protected bool               $isVisibilityFilterRequired;
    protected ?State             $state = null;

    /**
     * @param ResourceConnection $resource
     * @param Collection $collection
     * @param CollectionUpdaterRegistry $collectionUpdaterRegistry
     * @param OptionCollectionUpdaters $optionCollectionUpdaters
     * @param ValueCollectionUpdaters $valueCollectionUpdaters
     * @param CustomerVisibility $helperCustomerVisibility
     * @param Data $helperData
     * @param array $conditions
     * @param State $state
     */
    public function __construct(
        ResourceConnection        $resource,
        Collection                $collection,
        CollectionUpdaterRegistry $collectionUpdaterRegistry,
        OptionCollectionUpdaters  $optionCollectionUpdaters,
        ValueCollectionUpdaters   $valueCollectionUpdaters,
        CustomerVisibility        $helperCustomerVisibility,
        Data                      $helperData,
        array                     $conditions = [],
        ?State                    $state = null
    ) {
        parent::__construct(
            $collection,
            $resource,
            $collectionUpdaterRegistry,
            $optionCollectionUpdaters,
            $valueCollectionUpdaters,
            $helperData,
            $conditions
        );

        $this->connection                 = $resource->getConnection();
        $this->helperCustomerVisibility   = $helperCustomerVisibility;
        $this->isVisibilityFilterRequired = $this->helperCustomerVisibility->isVisibilityFilterRequired();
        $this->state                      = $state;
    }

    /**
     * Process option value collection by updaters
     */
    public function process()
    {
        Profiler::start('APO1: Process VALUE collection by updaters');
        $partFrom = $this->collection->getSelect()->getPart('from');

        if ($this->isVisibilityFilterRequired && $this->helperData->isEnabledIsDisabled()) {
            $this->collection->addFieldToFilter('main_table.disabled', '0');
        }

        $attributesToExclude = $this->valueCollectionUpdaters->getAttributesToExclude();

        /** @var AbstractUpdater $valueCollectionUpdatersItem */
        foreach ($this->valueCollectionUpdaters->getData() as $valueCollectionUpdatersItem) {
            Profiler::start('APO1: process VALUE collection updater ' . get_class($valueCollectionUpdatersItem));
            $alias = $valueCollectionUpdatersItem->getTableAlias();
            if (array_key_exists($alias, $partFrom)) {
                Profiler::stop('APO1: process VALUE collection updater ' . get_class($valueCollectionUpdatersItem));
                continue;
            }

            if ($this->state !== null && $this->state->getAreaCode() == 'frontend' && in_array($alias, $attributesToExclude)) {
                Profiler::stop('APO1: process VALUE collection updater ' . get_class($valueCollectionUpdatersItem));
                continue;
            }

            if ($valueCollectionUpdatersItem->determineJoinNecessity()) {
                $this->collection->getSelect()->joinLeft(
                    $valueCollectionUpdatersItem->getFromConditions($this->conditions),
                    $valueCollectionUpdatersItem->getOnConditionsAsString(),
                    $valueCollectionUpdatersItem->getColumns()
                );
            }
            Profiler::stop('APO1: process VALUE collection updater ' . get_class($valueCollectionUpdatersItem));
        }
        Profiler::stop('APO1: Process VALUE collection by updaters');
    }
}
