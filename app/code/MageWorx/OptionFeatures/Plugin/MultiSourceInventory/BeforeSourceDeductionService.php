<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace MageWorx\OptionFeatures\Plugin\MultiSourceInventory;


use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\Validation\ValidationException;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Exception\SkuIsNotAssignedToStockException;
use Magento\InventorySalesApi\Api\GetStockBySalesChannelInterface;
use Magento\InventorySourceDeductionApi\Model\GetSourceItemBySourceCodeAndSku;
use Magento\InventorySourceDeductionApi\Model\SourceDeductionRequestInterface;
use Magento\InventorySourceDeductionApi\Model\SourceDeductionService;
use MageWorx\OptionFeatures\Model\QtyMultiplier;
use Magento\InventorySalesApi\Api\Data\SalesEventInterface;

class BeforeSourceDeductionService
{
    protected Registry $registry;
    protected QtyMultiplier $qtyMultiplier;
    protected ?ObjectManagerInterface $objectManager = null;
    protected ProductRepositoryInterface $productRepository;

    /**
     * QtyMuliptilerSourceDeductionService constructor.
     *
     * @param Registry $registry
     * @param QtyMultiplier $qtyMultiplier
     * @param ObjectManagerInterface $objectManager
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        Registry $registry,
        QtyMultiplier $qtyMultiplier,
        ObjectManagerInterface $objectManager,
        ProductRepositoryInterface $productRepository
    ) {
        $this->registry          = $registry;
        $this->qtyMultiplier     = $qtyMultiplier;
        $this->objectManager     = $objectManager;
        $this->productRepository = $productRepository;
    }

    /**
     * @throws CouldNotSaveException
     * @throws FileSystemException
     * @throws SkuIsNotAssignedToStockException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws ValidationException
     * @throws InputException
     */
    public function beforeExecute(
        SourceDeductionService $subject,
        SourceDeductionRequestInterface $sourceDeductionRequest
    ): void {

        $salesEvent = $sourceDeductionRequest->getSalesEvent()->getType();

        if ($salesEvent == SalesEventInterface::EVENT_SHIPMENT_CREATED) {
            /* Maybe need rewrite in Magento\InventoryShipping\Model\GetItemsToDeductFromShipment*/
            /* for invoice + ship order place */
            if ($this->registry->registry('current_invoice')) {
                $dataToDeduct = $this->registry->registry('current_invoice');
            } else {
                $dataToDeduct = $this->registry->registry('current_shipment');
            }
            $multiplier = 1;
        } else {
            return;
        }

        if (!$dataToDeduct) {
            return;
        }

        $sourceItemsSave                 = $this->objectManager->get(
            SourceItemsSaveInterface::class
        );
        $getStockItemConfiguration       = $this->objectManager->get(
            GetStockItemConfigurationInterface::class
        );
        $getStockBySalesChannel          = $this->objectManager->get(
            GetStockBySalesChannelInterface::class
        );
        $getSourceItemBySourceCodeAndSku = $this->objectManager->get(
            GetSourceItemBySourceCodeAndSku::class
        );

        $currentItems = $dataToDeduct->getAllItems();
        $sourceItems  = [];
        $sourceCode   = $sourceDeductionRequest->getSourceCode();
        $salesChannel = $sourceDeductionRequest->getSalesChannel();
        $stockId      = $getStockBySalesChannel->execute($salesChannel)->getStockId();
        $qtyToDeduct  = [];
        foreach ($currentItems as $item) {
            $product                = $this->productRepository->getById($item->getProductId());
            $itemSku                = $product->getSku();
            $qty                    = (float)$item->getQty();
            $stockItemConfiguration = $getStockItemConfiguration->execute(
                $itemSku,
                $stockId
            );

            if (!$stockItemConfiguration->isManageStock()) {
                //We don't need to Manage Stock
                continue;
            }

            $currentQtyMultiplierQty = $this->qtyMultiplier->getQtyMultiplierQtyForCurrentItemQty(
                $item->getOrderItem(),
                $qty
            );

            if (!$currentQtyMultiplierQty) {
                continue;
            }

            $qty = ($currentQtyMultiplierQty - $qty) * $multiplier;

            if (isset($qtyToDeduct[$itemSku])) {
                $qtyToDeduct[$itemSku] += $qty;
            } else {
                $qtyToDeduct[$itemSku] = $qty;
            }
        }

        foreach ($currentItems as $item) {
            $itemSku = $product->getSku();

            if (!array_key_exists($itemSku, $qtyToDeduct)) {
                continue;
            }
            $sourceItem = $getSourceItemBySourceCodeAndSku->execute($sourceCode, $itemSku);
            if ($sourceItem->getQuantity() - $qtyToDeduct[$itemSku] >= 0) {
                $sourceItem->setQuantity($sourceItem->getQuantity() - $qtyToDeduct[$itemSku]);
                $sourceItems[] = $sourceItem;
            } else {
                throw new LocalizedException(
                    __('Not all of your products are available in the requested quantity.')
                );
            }
        }

        if (!empty($sourceItems)) {
            $sourceItemsSave->execute($sourceItems);
        }
    }
}
