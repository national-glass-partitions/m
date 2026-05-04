<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionInventory\Model;

use Magento\Catalog\Model\Product\Option;
use Magento\Catalog\Model\Product\Option\Value;
use Magento\Catalog\Model\Product\Option\Value as ProductOptionValueModel;
use Magento\Catalog\Model\ResourceModel\Product\Option\Value\CollectionFactory as ValueCollection;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Store\Model\Store;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionInventory\Helper\Stock as HelperStock;
use Psr\Log\LoggerInterface;

/**
 * Class RefundQty. Refund option values qty when order is cancel or credit memo.
 */
class RefundQty
{
    protected ValueCollection        $valueCollection;
    protected StockRegistryInterface $stockRegistry;
    protected HelperStock            $helperStock;
    protected BaseHelper             $baseHelper;
    protected LoggerInterface        $logger;

    /**
     * RefundQty constructor.
     *
     * @param ValueCollection $valueCollection
     */
    public function __construct(
        ValueCollection        $valueCollection,
        StockRegistryInterface $stockRegistry,
        HelperStock            $helperStock,
        BaseHelper             $baseHelper,
        LoggerInterface        $logger
    ) {
        $this->valueCollection = $valueCollection;
        $this->stockRegistry   = $stockRegistry;
        $this->helperStock     = $helperStock;
        $this->baseHelper      = $baseHelper;
        $this->logger          = $logger;
    }

    /**
     * Refund qty when order is cancele or credit memo.
     * Walk through the all order $items, find count qty to refund by the $qtyFieldName
     * and refund it for all option values in this order.
     *
     * @param OrderItemInterface[] $items
     * @param string $qtyFieldName
     * @return $this
     */
    public function refund(array $items, string $qtyFieldName): RefundQty
    {
        foreach ($items as $item) {
            $orderItemQtyReturned = $this->getOrderItemQtyReturned($item, $qtyFieldName);
            if (!$orderItemQtyReturned) {
                continue;
            }

            $valueIds             = $this->getValueIds($item);
            $this->updateValuesQty($valueIds, $item, $orderItemQtyReturned);
        }

        return $this;
    }

    /**
     * Get order item qty returned based on field name
     *
     * @param OrderItemInterface $item
     * @param string $qtyFieldName
     * @return float
     */
    protected function getOrderItemQtyReturned(OrderItemInterface $item, string $qtyFieldName): float
    {
        $qty = (float)$item->getQty();
        if (!$qty) {
            return 0;
        }

        $itemData       = $item->getData();
        $infoBuyRequest = $itemData['product_options']['info_buyRequest']['options'] ?? null;
        $options        = $itemData['product_options']['options'] ?? null;
        if (!$infoBuyRequest || !$options) {
            return 0;
        }

        $orderItemQtyReturned = $qtyFieldName == 'qty_refunded'
            ? $qty + $itemData['qty_invoiced'] - $itemData[$qtyFieldName]
            : (float)$itemData[$qtyFieldName];

        return $orderItemQtyReturned ?: 0;
    }

    /**
     * Get value ids
     *
     * @param OrderItemInterface $item
     * @return array
     */
    protected function getValueIds(OrderItemInterface $item): array
    {
        $itemData = $item->getData();

        $optionsData = [];
        foreach ($itemData['product_options']['options'] as $optionData) {
            $optionsData[$optionData['option_id']] = $optionData;
        }
        $valueIds = [];
        foreach ($itemData['product_options']['info_buyRequest']['options'] as $optionId => $value) {
            if (!isset($optionsData[$optionId]) ||
                !$this->baseHelper->isSelectableOption($optionsData[$optionId]['option_type'])
            ) {
                continue;
            }

            $option = $optionsData[$optionId];
            $optionType = $option['option_type'];
            if (in_array($optionType, [Option::OPTION_TYPE_CHECKBOX, Option::OPTION_TYPE_MULTIPLE]) && !is_array($value)) {
                $value = explode(',', $value);
                $value = array_map('intval', $value);
            }

            if (is_array($value)) {
                foreach ($value as $valueId) {
                    $valueIds[] = $valueId;
                }
            } else {
                $valueIds[] = $value;
            }
        }

        return $valueIds;
    }

    /**
     * Update values qty
     *
     * @param array $valueIds
     * @param OrderItemInterface $item
     * @param float $orderItemQtyReturned
     */
    protected function updateValuesQty(array $valueIds, OrderItemInterface $item, float $orderItemQtyReturned): void
    {
        $itemData = $item->getData();

        $infoBuyRequest        = $itemData['product_options']['info_buyRequest'];
        $valuesCollectionItems = $this->getValuesCollectionItems($valueIds);

        foreach ($valueIds as $valueId) {
            /** @var ProductOptionValueModel|\Magento\Catalog\Model\Product\Option\Value $valueModel */
            $valueModel = $valuesCollectionItems[$valueId];

            if (!$valueModel) {
                continue;
            }

            // The Manage Stock setting is based on the store id
            $valueModel->setStoreId(Store::DEFAULT_STORE_ID);
            if (!$valueModel->getManageStock()) {
                continue;
            }

            $totalQtyReturned = $this->getTotalQtyReturned($valueModel, $infoBuyRequest, $orderItemQtyReturned);
            $resultQty        = (float)$valueModel->getQty() + $totalQtyReturned;
            $valueModel->setQty($resultQty);

            if ($this->helperStock->validateLinkedQtyField() && $valueModel->getSkuIsValid()) {
                $this->updateLinkedProductStock($valueModel->getSku(), $resultQty);
            }
        }

        $this->saveValuesCollectionItems($valuesCollectionItems);
    }

    /**
     * Save values collection items
     *
     * @param Value[] $valuesCollectionItems
     */
    protected function saveValuesCollectionItems(array $valuesCollectionItems): void
    {
        foreach ($valuesCollectionItems as $valueModel) {
            try {
                $valueModel->getResource()->save($valueModel);
            } catch (\Exception $e) {
                $this->logger->critical($e);
                continue;
            }
        }
    }

    /**
     * Get values collection items by value ids
     *
     * @param array $valueIds
     * @return ProductOptionValueModel[]
     */
    protected function getValuesCollectionItems(array $valueIds): array
    {
        $valuesCollection = $this->valueCollection
            ->create()
            ->addPriceToResult(Store::DEFAULT_STORE_ID)
            ->getValuesByOption($valueIds)
            ->load();

        return $valuesCollection->getItems();
    }

    /**
     * Calculates and return total qty considering QtyInput of order item
     *
     * @param ProductOptionValueModel $valueModel
     * @param array $infoBuyRequest
     * @param float $orderItemQtyReturned
     * @return float
     */
    public function getTotalQtyReturned(
        ProductOptionValueModel $valueModel,
        array                   $infoBuyRequest,
        float                   $orderItemQtyReturned
    ): float {
        $optionId = $valueModel->getOptionId();
        $valueId  = $valueModel->getOptionTypeId();
        if (empty($infoBuyRequest['options_qty']) || empty($infoBuyRequest['options_qty'][$optionId])) {
            return $orderItemQtyReturned;
        }

        $valueQty = 1;
        if (!empty($infoBuyRequest['options_qty'][$optionId][$valueId])) {
            $valueQty = $infoBuyRequest['options_qty'][$optionId][$valueId];
        } elseif (!is_array($infoBuyRequest['options_qty'][$optionId])) {
            $valueQty = $infoBuyRequest['options_qty'][$optionId];
        }

        return $valueQty * $orderItemQtyReturned;
    }

    /**
     * @inheritdoc
     */
    public function updateLinkedProductStock(string $sku, float $qty): void
    {
        $stockItem = $this->stockRegistry->getStockItemBySku($sku);
        $stockItem->setQty($qty);
        $this->stockRegistry->updateStockItemBySku($sku, $stockItem);
    }
}
