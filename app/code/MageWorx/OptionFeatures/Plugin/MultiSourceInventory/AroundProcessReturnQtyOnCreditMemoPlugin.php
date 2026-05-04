<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace MageWorx\OptionFeatures\Plugin\MultiSourceInventory;

use Magento\Framework\ObjectManagerInterface;
use Magento\InventoryCatalogApi\Model\GetProductTypesBySkusInterface;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;
use Magento\InventorySalesApi\Model\GetSkuFromOrderItemInterface;
use Magento\InventorySalesApi\Model\ReturnProcessor\ProcessRefundItemsInterface;
use Magento\InventorySalesApi\Model\ReturnProcessor\Request\ItemsToRefundInterfaceFactory;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\SalesInventory\Model\Order\ReturnProcessor;
use MageWorx\OptionFeatures\Model\QtyMultiplier;
use MageWorx\OptionBase\Helper\Data as BaseHelper;

/**
 * Adapt return qty to stock for multi stock environment.
 */
class AroundProcessReturnQtyOnCreditMemoPlugin
{
    protected GetSkuFromOrderItemInterface $getSkuFromOrderItem;
    protected ItemsToRefundInterfaceFactory $itemsToRefundFactory;
    protected ProcessRefundItemsInterface $processRefundItems;
    protected IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType;
    protected GetProductTypesBySkusInterface $getProductTypesBySkus;
    protected QtyMultiplier $qtyMultiplier;
    protected ?ObjectManagerInterface $objectManager = null;
    protected BaseHelper $baseHelper;

    public function __construct(
        QtyMultiplier $qtyMultiplier,
        ObjectManagerInterface $objectManager,
        BaseHelper $baseHelper
    ) {
        $this->qtyMultiplier = $qtyMultiplier;
        $this->objectManager = $objectManager;
        $this->baseHelper    = $baseHelper;
    }

    /**
     * Process return to stock for multi stock environment.
     *
     * @param ReturnProcessor $subject
     * @param callable $proceed
     * @param CreditmemoInterface $creditmemo
     * @param OrderInterface $order
     * @param array $returnToStockItems
     * @param bool $isAutoReturn
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(
        ReturnProcessor $subject,
        callable $proceed,
        CreditmemoInterface $creditmemo,
        OrderInterface $order,
        array $returnToStockItems = [],
        bool $isAutoReturn = false
    ): void {

        if (!$this->baseHelper->isMSIModuleEnabled()) {
            return;
        }

        $getProductTypesBySkus                             = $this->objectManager->get(
            GetProductTypesBySkusInterface::class
        );
        $getSkuFromOrderItem                               = $this->objectManager->get(
            GetSkuFromOrderItemInterface::class
        );
        $isSourceItemManagementAllowedForProductType       = $this->objectManager->get(
            IsSourceItemManagementAllowedForProductTypeInterface::class
        );
        $processRefundItems                                = $this->objectManager->get(
            ProcessRefundItemsInterface::class
        );
        $itemsToRefundFactory                              = $this->objectManager->get(
            ItemsToRefundInterfaceFactory::class
        );
        $this->getProductTypesBySkus                       = $getProductTypesBySkus;
        $this->getSkuFromOrderItem                         = $getSkuFromOrderItem;
        $this->isSourceItemManagementAllowedForProductType = $isSourceItemManagementAllowedForProductType;
        $this->processRefundItems                          = $processRefundItems;
        $this->itemsToRefundFactory                        = $itemsToRefundFactory;

        $items = [];
        foreach ($creditmemo->getItems() as $item) {
            if ($isAutoReturn || in_array($item->getOrderItemId(), $returnToStockItems)) {
                $orderItem = $item->getOrderItem();
                $itemSku   = $this->getSkuFromOrderItem->execute($orderItem);

                if ($this->isValidItem($itemSku, $orderItem->getProductType())) {
                    $qty               = (float)$item->getQty();
                    $qtyWithMultiplier = $this->qtyMultiplier->getQtyMultiplierFromOrderItem($orderItem);
                    if ($qtyWithMultiplier != 0) {
                        $qty *= $qtyWithMultiplier;
                    }

                    $processedQty = $orderItem->getQtyInvoiced() - $orderItem->getQtyRefunded() + $qty;

                    $items[$itemSku] = [
                        'qty'          => ($items[$itemSku]['qty'] ?? 0) + $qty,
                        'processedQty' => ($items[$itemSku]['processedQty'] ?? 0) + (float)$processedQty,
                    ];
                }
            }
        }

        $itemsToRefund = [];
        foreach ($items as $sku => $data) {
            $itemsToRefund[] = $this->itemsToRefundFactory->create(
                [
                    'sku'          => $sku,
                    'qty'          => $data['qty'],
                    'processedQty' => $data['processedQty'],
                ]
            );
        }
        $this->processRefundItems->execute($order, $itemsToRefund, $returnToStockItems);
    }

    /**
     * Verify is item valid for return qty to stock.
     *
     * @param string $sku
     * @param string|null $typeId
     * @return bool
     */
    private function isValidItem(string $sku, ?string $typeId): bool
    {
        //TODO: https://github.com/magento-engcom/msi/issues/1761
        // If product type located in table sales_order_item is "grouped" replace it with "simple"
        if ($typeId === 'grouped') {
            $typeId = 'simple';
        }

        $productType = $typeId ?: $this->getProductTypesBySkus->execute(
            [$sku]
        )[$sku];

        return $this->isSourceItemManagementAllowedForProductType->execute($productType);
    }
}
