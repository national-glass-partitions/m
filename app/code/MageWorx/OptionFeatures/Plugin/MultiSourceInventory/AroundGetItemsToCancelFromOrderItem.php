<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace MageWorx\OptionFeatures\Plugin\MultiSourceInventory;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\ObjectManagerInterface;
use Magento\InventorySalesApi\Api\Data\ItemToSellInterface;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\InventorySalesApi\Model\GetSkuFromOrderItemInterface;
use Magento\InventorySalesApi\Api\Data\ItemToSellInterfaceFactory;
use Magento\Framework\Serialize\Serializer\Json;
use MageWorx\OptionFeatures\Model\QtyMultiplier;

class AroundGetItemsToCancelFromOrderItem
{
    protected GetSkuFromOrderItemInterface $getSkuFromOrderItem;
    protected ItemToSellInterfaceFactory $itemsToSellFactory;
    protected Json $jsonSerializer;
    protected QtyMultiplier $qtyMultiplier;
    protected ?ObjectManagerInterface $objectManager = null;

    public function __construct(
        Json $jsonSerializer,
        QtyMultiplier $qtyMultiplier,
        ObjectManagerInterface $objectManager
    ) {
        $this->jsonSerializer = $jsonSerializer;
        $this->qtyMultiplier  = $qtyMultiplier;
        $this->objectManager  = $objectManager;
    }

    /**
     * @param OrderItem $orderItem
     * @return ItemToSellInterface[]
     * @throws FileSystemException
     */
    public function aroundExecute($subject, callable $proceed, OrderItem $orderItem): array
    {
        $getSkuFromOrderItem = $this->objectManager->get(
            GetSkuFromOrderItemInterface::class
        );
        $itemsToSellFactory  = $this->objectManager->get(
            ItemToSellInterfaceFactory::class
        );

        $this->getSkuFromOrderItem = $getSkuFromOrderItem;
        $this->itemsToSellFactory  = $itemsToSellFactory;

        $itemsToCancel = [];
        if ($orderItem->getHasChildren()) {
            if (!$orderItem->isDummy(true)) {
                foreach ($this->processComplexItem($orderItem) as $item) {
                    $itemsToCancel[] = $item;
                }
            }
        } elseif (!$orderItem->isDummy(true)) {
            $itemSku              = $this->getSkuFromOrderItem->execute($orderItem);
            $qtyWithMultiplierQty = $this->qtyMultiplier->getQtyMultiplierQtyForCurrentItemQty(
                $orderItem,
                (float)$this->getQtyToCancel($orderItem)
            );

            $qty = $qtyWithMultiplierQty != 0 ? $qtyWithMultiplierQty : $this->getQtyToCancel($orderItem);

            $itemsToCancel[] = $this->itemsToSellFactory->create(
                [
                    'sku' => $itemSku,
                    'qty' => $qty
                ]
            );
        }

        return $this->groupItemsBySku($itemsToCancel);
    }

    /**
     * @param ItemToSellInterface[] $itemsToCancel
     * @return ItemToSellInterface[]
     */
    private function groupItemsBySku(array $itemsToCancel): array
    {
        $processingItems = $groupedItems = [];
        foreach ($itemsToCancel as $item) {
            if ($item->getQuantity() == 0) {
                continue;
            }
            if (empty($processingItems[$item->getSku()])) {
                $processingItems[$item->getSku()] = $item->getQuantity();
            } else {
                $processingItems[$item->getSku()] += $item->getQuantity();
            }
        }

        foreach ($processingItems as $sku => $qty) {
            $groupedItems[] = $this->itemsToSellFactory->create(
                [
                    'sku' => $sku,
                    'qty' => $qty
                ]
            );
        }

        return $groupedItems;
    }

    /**
     * @param OrderItem $orderItem
     * @return ItemToSellInterface[]
     */
    private function processComplexItem(OrderItem $orderItem): array
    {
        $itemsToCancel = [];
        foreach ($orderItem->getChildrenItems() as $item) {
            $productOptions = $item->getProductOptions();
            if (isset($productOptions['bundle_selection_attributes'])) {
                $bundleSelectionAttributes = $this->jsonSerializer->unserialize(
                    $productOptions['bundle_selection_attributes']
                );
                if ($bundleSelectionAttributes) {
                    $shippedQty      = $bundleSelectionAttributes['qty'] * $orderItem->getQtyShipped();
                    $qty             = $item->getQtyOrdered() - max(
                            $shippedQty,
                            $item->getQtyInvoiced()
                        ) - $item->getQtyCanceled();
                    $itemSku         = $this->getSkuFromOrderItem->execute($item);
                    $itemsToCancel[] = $this->itemsToSellFactory->create(
                        [
                            'sku' => $itemSku,
                            'qty' => $qty
                        ]
                    );
                }
            } else {
                // configurable product
                $itemSku         = $this->getSkuFromOrderItem->execute($orderItem);
                $itemsToCancel[] = $this->itemsToSellFactory->create(
                    [
                        'sku' => $itemSku,
                        'qty' => $this->getQtyToCancel($orderItem)
                    ]
                );
            }
        }

        return $itemsToCancel;
    }

    private function getQtyToCancel(OrderItem $item): float
    {
        return $item->getQtyOrdered() - max($item->getQtyShipped(), $item->getQtyInvoiced()) - $item->getQtyCanceled();
    }
}
