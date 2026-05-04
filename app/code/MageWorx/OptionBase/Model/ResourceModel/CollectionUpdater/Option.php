<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionBase\Model\ResourceModel\CollectionUpdater;

use Magento\Framework\App\ResourceConnection;
use Magento\Catalog\Model\ResourceModel\Product\Option\Collection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Profiler;
use MageWorx\OptionBase\Model\Product\Option\AbstractUpdater;
use MageWorx\OptionBase\Model\ResourceModel\CollectionUpdaterAbstract;
use MageWorx\OptionBase\Model\ResourceModel\CollectionUpdaterRegistry;
use MageWorx\OptionBase\Model\Product\Option\CollectionUpdaters as OptionCollectionUpdaters;
use MageWorx\OptionBase\Model\Product\Option\Value\CollectionUpdaters as ValueCollectionUpdaters;
use Magento\Framework\App\State;
use MageWorx\OptionBase\Helper\Data;
use MageWorx\OptionBase\Helper\CustomerVisibility;

class Option extends CollectionUpdaterAbstract
{
    protected State $state;
    protected CustomerVisibility $helperCustomerVisibility;
    protected int $customerGroupId;
    protected int $customerStoreId;
    protected bool $isEnabledCustomerGroup;
    protected bool $isEnabledCustomerStoreView;
    protected bool $isVisibilityFilterRequired;
    protected AdapterInterface $connection;
    protected array $selfLoadedAttributes = ['option_title', 'description', 'option_price'];

    public function __construct(
        ResourceConnection $resource,
        Collection $collection,
        CollectionUpdaterRegistry $collectionUpdaterRegistry,
        OptionCollectionUpdaters $optionCollectionUpdaters,
        ValueCollectionUpdaters $valueCollectionUpdaters,
        State $state,
        Data $helperData,
        CustomerVisibility $helperCustomerVisibility,
        $conditions = []
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
        $this->state                      = $state;
        $this->helperCustomerVisibility   = $helperCustomerVisibility;
        $this->isEnabledCustomerGroup     = $this->helperData->isEnabledVisibilityPerCustomerGroup();
        $this->isEnabledCustomerStoreView = $this->helperData->isEnabledVisibilityPerCustomerStoreView();
        $this->customerGroupId            = $this->helperCustomerVisibility->getCurrentCustomerGroupId();
        $this->customerStoreId            = $this->helperCustomerVisibility->getCurrentCustomerStoreId();
        $this->isVisibilityFilterRequired = $this->helperCustomerVisibility->isVisibilityFilterRequired();
    }

    /**
     * Process option collection by updaters
     */
    public function process()
    {
        Profiler::start('APO1: Process OPTION collection by updaters');
        $partFrom = $this->collection->getSelect()->getPart('from');
        $this->collection->setFlag('mw_avoid_adding_options_attributes', true);

        if ($this->isVisibilityFilterRequired && $this->helperData->isEnabledIsDisabled()) {
            $this->collection->addFieldToFilter('main_table.disabled', '0');
            $this->collection->addFieldToFilter('main_table.disabled_by_values', '0');
        }

        /**
         * @var string $name
         * @var AbstractUpdater $optionCollectionUpdatersItem
         */
        foreach ($this->optionCollectionUpdaters->getData() as $name => $optionCollectionUpdatersItem) {
            Profiler::start('APO1: process OPTION collection updater ' . get_class($optionCollectionUpdatersItem));
            $alias = $optionCollectionUpdatersItem->getTableAlias();
            if (array_key_exists($alias, $partFrom)) {
                Profiler::stop('APO1: process OPTION collection updater ' . get_class($optionCollectionUpdatersItem));
                continue;
            }

            // Disable attribute loading. Load them separately to improve the performance of the main query.
            if ($this->state->getAreaCode() == 'frontend' && in_array($name, $this->selfLoadedAttributes)) {
                continue;
            }

            // Here we are checking the update is required or not.
            // It must replace previous conditions with state or in_array check
            if ($optionCollectionUpdatersItem->determineJoinNecessity()) {
                $this->collection->getSelect()->joinLeft(
                    $optionCollectionUpdatersItem->getFromConditions($this->conditions),
                    $optionCollectionUpdatersItem->getOnConditionsAsString(),
                    $optionCollectionUpdatersItem->getColumns()
                );
            }
            Profiler::stop('APO1: process OPTION collection updater ' . get_class($optionCollectionUpdatersItem));
        }

        //Added check "visibility_by_customer_group_id" for compatibility with Amasty Abandoned Cart Email
        $columns = $this->collection->getSelect()->getPart('columns');

        if ($this->isVisibilityFilterRequired && $this->isEnabledCustomerGroup) {
            foreach ($columns as $column) {
                if ($column[2] == 'visibility_by_customer_group_id') {
                    $this->collection->getSelect()->having('visibility_by_customer_group_id', $this->customerGroupId);

                    break;
                }
            }
        }

        if ($this->isVisibilityFilterRequired && $this->isEnabledCustomerStoreView && $this->customerStoreId) {
            foreach ($columns as $column) {
                if ($column[2] == 'visibility_by_customer_store_id') {
                    $this->collection->getSelect()->having('visibility_by_customer_store_id', $this->customerStoreId);

                    break;
                }
            }
        }
        Profiler::stop('APO1: Process OPTION collection by updaters');
    }
}
