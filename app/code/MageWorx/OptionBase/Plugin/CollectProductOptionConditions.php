<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionBase\Plugin;

use Magento\Customer\Model\Group;
use Magento\Framework\App\State;
use MageWorx\OptionBase\Model\ResourceModel\CollectionUpdaterRegistry;
use Magento\Catalog\Model\ResourceModel\Product\Option\Value\CollectionFactory as OptionValueCollectionFactory;
use Magento\Store\Model\StoreManagerInterface as StoreManager;
use MageWorx\OptionBase\Helper\System as SystemHelper;
use Magento\Framework\Serialize\Serializer\Json as Serializer;
use MageWorx\OptionBase\Model\ConditionValidator;
use Magento\Framework\Event\ManagerInterface;
use MageWorx\OptionBase\Helper\Data as BaseHelper;

class CollectProductOptionConditions
{
    protected State $state;
    private CollectionUpdaterRegistry $collectionUpdaterRegistry;
    protected OptionValueCollectionFactory $optionValueCollectionFactory;
    protected StoreManager $storeManager;
    protected SystemHelper $systemHelper;
    protected array $valuesCollectionCache = [];
    protected Serializer $serializer;
    protected ConditionValidator $conditionValidator;
    protected string $customerGroupId;
    protected ManagerInterface $eventManager;
    protected BaseHelper $baseHelper;

    /**
     * CollectProductOptionConditions constructor.
     *
     * @param CollectionUpdaterRegistry $collectionUpdaterRegistry
     * @param OptionValueCollectionFactory $optionValueCollectionFactory
     * @param SystemHelper $systemHelper
     * @param StoreManager $storeManager
     * @param State $state
     * @param Serializer $serializer
     * @param ConditionValidator $conditionValidator
     * @param ManagerInterface $eventManager
     */
    public function __construct(
        CollectionUpdaterRegistry $collectionUpdaterRegistry,
        OptionValueCollectionFactory $optionValueCollectionFactory,
        SystemHelper $systemHelper,
        StoreManager $storeManager,
        State $state,
        Serializer $serializer,
        ConditionValidator $conditionValidator,
        ManagerInterface $eventManager,
        BaseHelper $baseHelper
    ) {
        $this->collectionUpdaterRegistry    = $collectionUpdaterRegistry;
        $this->optionValueCollectionFactory = $optionValueCollectionFactory;
        $this->systemHelper                 = $systemHelper;
        $this->storeManager                 = $storeManager;
        $this->state                        = $state;
        $this->serializer                   = $serializer;
        $this->conditionValidator           = $conditionValidator;
        $this->eventManager                 = $eventManager;
        $this->baseHelper                   = $baseHelper;
    }

    /**
     * Set product ID to collection updater registry for future use in collection updaters
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Option\Collection $object
     * @param int $productId
     * @param int $storeId
     * @param bool $requiredOnly
     * @return array
     */
    public function beforeGetProductOptions($object, $productId, $storeId, $requiredOnly = false)
    {
        $this->collectionUpdaterRegistry->setCurrentEntityIds([$productId]);
        $this->collectionUpdaterRegistry->setCurrentEntityType('product');

        if ($this->systemHelper->isOptionImportAction()) {
            $this->collectionUpdaterRegistry->setOptionIds([]);
            $this->collectionUpdaterRegistry->setOptionValueIds([]);
        }

        return [$productId, $storeId, $requiredOnly];
    }

    /**
     * Set option/option value IDs to collection updater registry for future use in collection updaters
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Option\Collection $subject
     * @param \Closure $proceed
     * @param int $storeId
     * @return \Magento\Catalog\Model\ResourceModel\Product\Option\Collection
     */
    public function aroundAddValuesToResult($subject, \Closure $proceed, $storeId = null)
    {
        \Magento\Framework\Profiler::start('optionBase-collectProductOptionCondition-aroundAddValuesToResult');

        if ($storeId === null) {
            $storeId = $this->storeManager->getStore()->getId();
        }
        $optionIds = [];

        /* maybe need to set optionCollection to variable, because further in the code we call
           optionCollection by each option value ($subject) */
        foreach ($subject as $option) {
            if (!$option->getId()) {
                continue;
            }
            $optionIds[] = $option->getId();
        }

        if ($optionIds) {
            $this->collectionUpdaterRegistry->setOptionIds($optionIds);
        }

        if (!empty($optionIds)) {
            /** @var \Magento\Catalog\Model\ResourceModel\Product\Option\Value\Collection $values */
            $values = $this->optionValueCollectionFactory->create();
            $values->addTitleToResult(
                $storeId
            )->addPriceToResult(
                $storeId
            )->addOptionToFilter(
                $optionIds
            )->setOrder(
                'sort_order',
                'asc'
            )->setOrder(
                'title',
                'asc'
            );

            $valueIds = [];

            $isGraphQlRequest = false;
            if ('graphql' === $this->state->getAreaCode()) {
                $this->customerGroupId = (int)$this->systemHelper->resolveCurrentCustomerGroupId();
                $isGraphQlRequest      = true;
            }

            foreach ($values as $value) {
                if (!$value->getOptionTypeId()) {
                    continue;
                }
                $valueIds[] = $value->getOptionTypeId();
                $optionId   = $value->getOptionId();
                $option     = $subject->getItemById($optionId);

                // for graphql requests
                if ($isGraphQlRequest) {
                    $this->setPriceForCurrentCustomerGroup('tier_price', $value);
                    $this->setPriceForCurrentCustomerGroup('special_price', $value);
                }

                if ($option) {
                    $option->addValue($value);
                    $value->setOption($option);
                }
            }

            if ($this->baseHelper->isModuleEnabled('Magento_InventorySalesAdminUi')) {
                $this->eventManager->dispatch(
                    'mageworx_optioninventory_linked_qty_source_update',
                    ['data_to_update' => $values, 'option_collection' => $subject]
                );
            }

            if ($valueIds) {
                $this->collectionUpdaterRegistry->setOptionValueIds($valueIds);
            }
        }

        /* Front Product
         *
         * Origin code
         *
         * time - 5.597329
         * avg - 5.597329
         * memory - 4,822,160
         *
         * -------------------------
         *
         * Updated code (added flag for option filters added)
         *
         * time - 2.778387 (3.743456)
         * avg - 2.778387   (3.743456)
         * memory - 4,822,160
         * real memory- 6,291,456
         *
         */

        /* Backend Product
         *
         * Origin code
         *
         * time - 4.234509 (second 3.920867)
         * avg - 4.234509 (second 3.920867)
         * memory - 4,491,000   (second 4,491,000)
         * real memory - (second 4,194,304)
         *
         * -------------------------
         *
         * Updated code
         *
         * time - 3.104811 (second 2.931551)
         * avg - 3.104811 (second 2.931551)
         * memory - 4,485,368   (second 4,491,000)
         * real memory - (second 4,194,304)
         *
         */
        \Magento\Framework\Profiler::stop('optionBase-collectProductOptionCondition-aroundAddValuesToResult');

        return $subject;
    }

    /**
     * Set Advanced Pricing price for current customer group
     *
     * @param string $priceType
     * @param \Magento\Catalog\Model\ResourceModel\Product\Option\Value $value
     * @return void
     */
    protected function setPriceForCurrentCustomerGroup($priceType, $value)
    {
        $optionValuePrice             = $value->getDataByKey('default_price');
        $pricesAdvancedPricing        = $value->getDataByKey($priceType);
        $decodedPricesAdvancedPricing = $pricesAdvancedPricing ? $this->serializer->unserialize(
            $pricesAdvancedPricing
        ) : null;

        if ($decodedPricesAdvancedPricing && is_integer($this->customerGroupId)) {
            $priceForCurrentCustomerGroup = [];
            $priceForAllGroups            = [];

            foreach ($decodedPricesAdvancedPricing as $priceItem) {
                if (!$this->conditionValidator->isValidated(
                    $priceItem,
                    $optionValuePrice
                )
                ) {
                    continue;
                }

                // TODO need to add 'default case
                switch ($priceItem['customer_group_id']) {
                    case $this->customerGroupId:
                        $priceForCurrentCustomerGroup = $priceItem;
                        break;
                    case Group::CUST_GROUP_ALL:
                        $priceForAllGroups = $priceItem;
                        break;
                }
            }

            if (!empty($priceForCurrentCustomerGroup)) {
                $resultPrice = $this->serializer->serialize($priceForCurrentCustomerGroup);
            } elseif (!empty($priceForAllGroups)) {
                $resultPrice = $this->serializer->serialize($priceForAllGroups);
            } else {
                $resultPrice = null;
            }

            $value->setData(
                $priceType,
                $resultPrice
            );
        }
    }
}
