<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace MageWorx\OptionFeatures\Plugin\MultiSourceInventory;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\InventorySalesApi\Model\GetSkuFromOrderItemInterface;
use Magento\InventorySourceDeductionApi\Model\IsItemCouldBeDeductedByTypes;
use Magento\InventorySourceDeductionApi\Model\ItemToDeductInterface;
use Magento\InventorySourceDeductionApi\Model\ItemToDeductInterfaceFactory;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order\Shipment\Item;
use MageWorx\OptionFeatures\Model\QtyMultiplier;

/**
 * Get source items for deduction class.
 */
class AroundGetItemsToDeductFromShipment
{
    protected GetSkuFromOrderItemInterface $getSkuFromOrderItem;
    protected Json $jsonSerializer;
    protected ItemToDeductInterfaceFactory $itemToDeduct;
    protected IsItemCouldBeDeductedByTypes $itemCouldBeDeducted;
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
     * Get source items for deduction for specified shipment.
     *
     * @param Shipment $shipment
     * @return ItemToDeductInterface[]
     * @throws NoSuchEntityException
     */
    public function aroundExecute($subject, callable $proceed, Shipment $shipment): array
    {
        $getSkuFromOrderItem = $this->objectManager->get(
            GetSkuFromOrderItemInterface::class
        );
        $itemCouldBeDeducted = $this->objectManager->get(
            IsItemCouldBeDeductedByTypes::class
        );
        $itemToDeduct        = $this->objectManager->get(
            ItemToDeductInterfaceFactory::class
        );

        $this->getSkuFromOrderItem = $getSkuFromOrderItem;
        $this->itemCouldBeDeducted = $itemCouldBeDeducted;
        $this->itemToDeduct        = $itemToDeduct;


        $itemsToShip = [];

        /** @var \Magento\Sales\Model\Order\Shipment\Item $shipmentItem */
        foreach ($shipment->getAllItems() as $shipmentItem) {
            $orderItem = $shipmentItem->getOrderItem();
            // This code was added as quick fix for merge mainline
            // https://github.com/magento-engcom/msi/issues/1586
            if (null === $orderItem || $this->shouldBeExcluded($orderItem)) {
                continue;
            }
            if ($orderItem->getHasChildren()) {
                if (!$orderItem->isDummy(true)) {
                    foreach ($this->processComplexItem($shipmentItem) as $item) {
                        $itemsToShip[] = $item;
                    }
                }
            } else {
                $itemSku              = $this->getSkuFromOrderItem->execute($orderItem);
                $qty                  = (float)$this->castQty($orderItem, $shipmentItem->getQty());
                $qtyWithMultiplierQty = $this->qtyMultiplier->getQtyMultiplierQtyForCurrentItemQty(
                    $orderItem,
                    $qty
                );

                $qty = $qtyWithMultiplierQty != 0 ? $qtyWithMultiplierQty : $qty;


                $itemsToShip[] = $this->itemToDeduct->create(
                    [
                        'sku' => $itemSku,
                        'qty' => $qty,
                    ]
                );
            }
        }

        return $this->groupItemsBySku($itemsToShip);
    }

    /**
     * Group shipment items by product they belong.
     *
     * @param array $shipmentItems
     * @return array
     */
    private function groupItemsBySku(array $shipmentItems): array
    {
        $processingItems = $groupedItems = [];
        foreach ($shipmentItems as $shipmentItem) {
            if (empty($processingItems[$shipmentItem->getSku()])) {
                $processingItems[$shipmentItem->getSku()] = $shipmentItem->getQty();
            } else {
                $processingItems[$shipmentItem->getSku()] += $shipmentItem->getQty();
            }
        }

        foreach ($processingItems as $sku => $qty) {
            $groupedItems[] = $this->itemToDeduct->create(
                [
                    'sku' => $sku,
                    'qty' => $qty,
                ]
            );
        }

        return $groupedItems;
    }

    /**
     * Process shipment item for complex products.
     *
     * @param Item $shipmentItem
     * @return array
     */
    private function processComplexItem(Item $shipmentItem): array
    {
        $orderItem   = $shipmentItem->getOrderItem();
        $itemsToShip = [];
        foreach ($orderItem->getChildrenItems() as $item) {
            if ($item->getIsVirtual() || $item->getLockedDoShip()) {
                continue;
            }
            $productOptions = $item->getProductOptions();
            if (isset($productOptions['bundle_selection_attributes'])) {
                $bundleSelectionAttributes = $this->jsonSerializer->unserialize(
                    $productOptions['bundle_selection_attributes']
                );
                if ($bundleSelectionAttributes) {
                    $qty           = $bundleSelectionAttributes['qty'] * $shipmentItem->getQty();
                    $qty           = $this->castQty($item, $qty);
                    $itemSku       = $this->getSkuFromOrderItem->execute($item);
                    $itemsToShip[] = $this->itemToDeduct->create(
                        [
                            'sku' => $itemSku,
                            'qty' => $qty,
                        ]
                    );
                    continue;
                }
            } else {
                // configurable product
                $itemSku       = $this->getSkuFromOrderItem->execute($orderItem);
                $qty           = $this->castQty($orderItem, $shipmentItem->getQty());
                $itemsToShip[] = $this->itemToDeduct->create(
                    [
                        'sku' => $itemSku,
                        'qty' => $qty,
                    ]
                );
            }
        }

        return $itemsToShip;
    }

    /**
     * Get quantity for order item.
     *
     * @param OrderItem $item
     * @param string|int|float $qty
     * @return float|int
     */
    private function castQty(OrderItem $item, $qty)
    {
        if ($item->getIsQtyDecimal()) {
            $qty = (double)$qty;
        } else {
            $qty = (int)$qty;
        }

        return $qty > 0 ? $qty : 0;
    }

    /**
     * Verify, if product should be processed for deduction.
     *
     * @param OrderItem $orderItem
     * @return bool
     */
    private function shouldBeExcluded(OrderItem $orderItem): bool
    {
        return $orderItem->getProduct() === null
            || !$this->itemCouldBeDeducted->execute(
                $orderItem->getProductType(),
                $orderItem->getProduct()->getTypeId()
            );
    }
}
