<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionInventory\Plugin;

use Magento\Catalog\Api\ProductRepositoryInterface;
use MageWorx\OptionInventory\Helper\Stock as HelperStock;
use MageWorx\OptionInventory\Model\ResourceModel\Product\Option\Value\Collection;
use Magento\Catalog\Model\ResourceModel\Product\Option\Value\CollectionFactory as OptionValueCollectionFactory;
use MageWorx\OptionInventory\Model\ResourceModel\Product\CollectionUpdaterStock;
use MageWorx\OptionBase\Helper\Data as BaseHelper;

/**
 * Class OrderManagement
 */
class UpdateLinkedValueQtyAfterPlaceOrder
{
    protected OptionValueCollectionFactory $optionValueCollectionFactory;
    protected Collection $inventoryValueCollection;
    protected CollectionUpdaterStock $collectionUpdaterStock;
    protected BaseHelper $baseHelper;
    protected HelperStock $helperStock;

    /**
     * UpdateLinkedValueQtyAfterPlaceOrder constructor.
     *
     * @param Collection $inventoryValueCollection
     * @param OptionValueCollectionFactory $optionValueCollectionFactory
     * @param CollectionUpdaterStock $collectionUpdaterStock
     * @param ProductRepositoryInterface $productRepository
     * @param BaseHelper $baseHelper
     * @param HelperStock $helperData
     */
    public function __construct(
        Collection $inventoryValueCollection,
        OptionValueCollectionFactory $optionValueCollectionFactory,
        CollectionUpdaterStock $collectionUpdaterStock,
        ProductRepositoryInterface $productRepository,
        BaseHelper $baseHelper,
        HelperStock $helperStock
    ) {
        $this->inventoryValueCollection     = $inventoryValueCollection;
        $this->optionValueCollectionFactory = $optionValueCollectionFactory;
        $this->collectionUpdaterStock       = $collectionUpdaterStock;
        $this->baseHelper                   = $baseHelper;
        $this->helperStock                  = $helperStock;
    }

    /**
     * @param \Magento\Quote\Model\QuoteManagement $subject
     * @param \Magento\Sales\Model\Order $result
     * @param \Magento\Quote\Model\Quote $quote
     * @param array $orderData
     * @return \Magento\Sales\Model\Order
     */
    public function afterSubmit(
        \Magento\Quote\Model\QuoteManagement $subject,
        \Magento\Sales\Model\Order $result,
        \Magento\Quote\Model\Quote $quote,
        $orderData = []
    ) {
        if (!$this->helperStock->validateLinkedQtyField()) {
            return $result;
        }

        $quoteItems = $quote->getAllItems();
        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        foreach ($quoteItems as $quoteItem) {
            if (!$this->validateItem($quoteItem) || $quoteItem->getProductType() == 'bundle') {
                continue;
            }
            $buyRequest = $quoteItem->getBuyRequest();

            /** @var array $options */
            $options = $buyRequest->getOptions();
            if ($options) {
                if (!is_array($options)) {
                    continue;
                }
                $itemValues             = $this->getItemValues(array_keys($options));
                $linkedValueQtyToUpdate = $this->prepareLinkedValueQtyData($options, $itemValues);

                if ($linkedValueQtyToUpdate) {
                    $this->collectionUpdaterStock->updateLinkedValueQtyAfterPlaceOrder($linkedValueQtyToUpdate);
                }
            } else {
                $productSku = $quoteItem->getSku();
                $this->collectionUpdaterStock->updateLinkedValueQty(
                    $productSku,
                    $this->getCurrentSalableQty($productSku)
                );
            }

        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    protected function prepareLinkedValueQtyData(array $options, $itemValues): array
    {
        $linkedValueQtyToUpdate = [];
        foreach ($options as $optionId => $optionValue) {
            if (!is_array($optionValue)) {
                $optionValues = explode(',', $optionValue);
            } else {
                $optionValues = $optionValue;
            }

            foreach ($optionValues as $valueId) {
                // option type file, can have an array of values
                if (is_array($valueId)) {
                    continue;
                }

                $value = isset($itemValues[$valueId]) ? $itemValues[$valueId] : null;
                if (!$value) {
                    continue;
                }
                if ($value->getSkuIsValid()) {
                    $linkedValueQtyToUpdate[] = [
                        'option_type_id' => $value->getOptionTypeId(),
                        'qty'            => $this->getCurrentSalableQty($value->getSku())
                    ];
                }
            }
        }

        return $linkedValueQtyToUpdate;
    }

    protected function getCurrentSalableQty(string $sku): float
    {
        return $this->baseHelper->updateValueQtyToSalableQty($sku);
    }

    protected function validateItem(\Magento\Quote\Model\Quote\Item $quoteItem): bool
    {
        $buyRequest = $quoteItem->getBuyRequest();
        if (!$buyRequest) {
            return false;
        }

        return true;
    }

    /**
     * Get all options values
     *
     * @param $optionIds
     * @return array
     */
    protected function getItemValues(array $optionIds): array
    {
        $optionValueCollection = $this->optionValueCollectionFactory->create();
        $valuesItems           = $optionValueCollection->addOptionToFilter($optionIds)->getItems();

        return $valuesItems;
    }
}
