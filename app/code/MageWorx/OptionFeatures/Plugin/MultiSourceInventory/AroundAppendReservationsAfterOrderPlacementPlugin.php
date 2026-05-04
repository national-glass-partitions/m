<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace MageWorx\OptionFeatures\Plugin\MultiSourceInventory;

use Magento\InventoryCatalogApi\Model\GetProductTypesBySkusInterface;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;
use Magento\InventorySales\Model\AppendReservations;
use Magento\InventorySales\Model\CheckItemsQuantity;
use Magento\InventorySales\Model\ReservationExecutionInterface;
use Magento\InventorySalesApi\Api\Data\ItemToSellInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\SalesEventExtensionInterface;
use Magento\InventorySalesApi\Api\Data\SalesEventInterface;
use Magento\InventorySalesApi\Api\Data\SalesEventInterfaceFactory;
use Magento\InventorySalesApi\Api\PlaceReservationsForSalesEventInterface;
use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionFeatures\Model\QtyMultiplier;
use Magento\Framework\ObjectManagerInterface;
use Magento\InventorySalesApi\Api\Data\SalesEventExtensionFactory;

/**
 * Add reservation during order placement
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AroundAppendReservationsAfterOrderPlacementPlugin
{
    protected PlaceReservationsForSalesEventInterface $placeReservationsForSalesEvent;
    protected GetSkusByProductIdsInterface $getSkusByProductIds;
    protected GetProductTypesBySkusInterface $getProductTypesBySkus;
    protected SalesEventInterfaceFactory $salesEventFactory;
    protected ItemToSellInterfaceFactory $itemsToSellFactory;
    protected IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType;
    protected AppendReservations $appendReservations;
    protected ReservationExecutionInterface $reservationExecution;
    protected QtyMultiplier $qtyMultiplier;
    protected ?ObjectManagerInterface $objectManager = null;
    protected BaseHelper $baseHelper;
    protected WebsiteRepositoryInterface $websiteRepository;
    protected SalesEventExtensionFactory $salesEventExtensionFactory;
    protected StockByWebsiteIdResolverInterface $stockByWebsiteIdResolver;
    protected CheckItemsQuantity $checkItemsQuantity;
    protected SalesChannelInterfaceFactory $salesChannelFactory;

    public function __construct(
        QtyMultiplier $qtyMultiplier,
        ObjectManagerInterface $objectManager,
        BaseHelper $baseHelper,
        WebsiteRepositoryInterface $websiteRepository
    ) {
        $this->qtyMultiplier     = $qtyMultiplier;
        $this->objectManager     = $objectManager;
        $this->baseHelper        = $baseHelper;
        $this->websiteRepository = $websiteRepository;
    }

    /**
     * Add inventory reservation before placing synchronous order or if stock reservation is deferred.
     *
     * @param OrderManagementInterface $subject
     * @param callable $proceed
     * @param OrderInterface $order
     * @return OrderInterface
     * @throws \Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundPlace(
        OrderManagementInterface $subject,
        callable $proceed,
        OrderInterface $order
    ): OrderInterface {

        if (!$this->baseHelper->isMSIModuleEnabled()) {
            return $proceed($order);
        }

        $getProductTypesBySkus                             = $this->objectManager->get(
            GetProductTypesBySkusInterface::class
        );
        $getSkusByProductIds                               = $this->objectManager->get(
            GetSkusByProductIdsInterface::class
        );
        $isSourceItemManagementAllowedForProductType       = $this->objectManager->get(
            IsSourceItemManagementAllowedForProductTypeInterface::class
        );
        $itemsToSellFactory                                = $this->objectManager->get(
            ItemToSellInterfaceFactory::class
        );
        $salesEventFactory                                 = $this->objectManager->get(
            SalesEventInterfaceFactory::class
        );
        $placeReservationsForSalesEvent                    = $this->objectManager->get(
            PlaceReservationsForSalesEventInterface::class
        );
        $this->getProductTypesBySkus                       = $getProductTypesBySkus;
        $this->getSkusByProductIds                         = $getSkusByProductIds;
        $this->isSourceItemManagementAllowedForProductType = $isSourceItemManagementAllowedForProductType;
        $this->itemsToSellFactory                          = $itemsToSellFactory;
        $this->salesEventFactory                           = $salesEventFactory;
        $this->placeReservationsForSalesEvent              = $placeReservationsForSalesEvent;

        $itemsById = $itemsBySku = $itemsToSell = [];
        foreach ($order->getItems() as $item) {
            if (!isset($itemsById[$item->getProductId()])) {
                $itemsById[$item->getProductId()] = 0;
            }
            $qtyWithMultiplierQty = $this->qtyMultiplier->getQtyMultiplierQtyForCurrentItemQty(
                $item,
                (float)$item->getQtyOrdered()
            );

            $qty = $qtyWithMultiplierQty != 0 ? $qtyWithMultiplierQty : $item->getQtyOrdered();

            $itemsById[$item->getProductId()] += $qty;
        }
        $productSkus  = $this->getSkusByProductIds->execute(array_keys($itemsById));
        $productTypes = $this->getProductTypesBySkus->execute($productSkus);

        foreach ($productSkus as $productId => $sku) {
            if (false === $this->isSourceItemManagementAllowedForProductType->execute($productTypes[$sku])) {
                continue;
            }

            $itemsBySku[$sku] = (float)$itemsById[$productId];
            $itemsToSell[]    = $this->itemsToSellFactory->create(
                [
                    'sku' => $sku,
                    'qty' => -(float)$itemsById[$productId]
                ]
            );
        }
        $websiteId = (int)$order->getStore()->getWebsiteId();

        if ($this->baseHelper->checkModuleVersion('1.3.1', '', '>=', '<', 'Magento_InventorySales')) {
            return $this->aroundPlaceProcessForMagento246AndHigher($order, $itemsBySku, $itemsToSell, $proceed, $websiteId);
        }

        return $this->aroundPlaceProcess($order, $itemsBySku, $itemsToSell, $proceed, $websiteId);
    }

    /**
     * Create new Order
     *
     * In case of error during order placement exception add compensation
     *
     * @param callable $proceed
     * @param OrderInterface $order
     * @param array $itemsToSell
     * @param SalesChannelInterface $salesChannel
     * @param SalesEventExtensionInterface $salesEventExtension
     * @return OrderInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function createOrder($proceed, $order, $itemsToSell, $salesChannel, $salesEventExtension)
    {
        try {
            $order = $proceed($order);
        } catch (\Exception $e) {
            //add compensation
            foreach ($itemsToSell as $item) {
                $item->setQuantity(-(float)$item->getQuantity());
            }

            /** @var SalesEventInterface $salesEvent */
            $salesEvent = $this->salesEventFactory->create(
                [
                    'type'       => SalesEventInterface::EVENT_ORDER_PLACE_FAILED,
                    'objectType' => SalesEventInterface::OBJECT_TYPE_ORDER,
                    'objectId'   => (string)$order->getEntityId()
                ]
            );
            $salesEvent->setExtensionAttributes($salesEventExtension);

            $this->placeReservationsForSalesEvent->execute($itemsToSell, $salesChannel, $salesEvent);

            throw $e;
        }

        return $order;
    }

    public function aroundPlaceProcess($order, $itemsBySku, $itemsToSell, $proceed, $websiteId)
    {
        $salesChannelFactory        = $this->objectManager->get(
            SalesChannelInterfaceFactory::class
        );
        $checkItemsQuantity         = $this->objectManager->get(
            CheckItemsQuantity::class
        );
        $stockByWebsiteIdResolver   = $this->objectManager->get(
            StockByWebsiteIdResolverInterface::class
        );
        $salesEventExtensionFactory = $this->objectManager->get(
            SalesEventExtensionFactory::class
        );

        $this->salesEventExtensionFactory = $salesEventExtensionFactory;
        $this->stockByWebsiteIdResolver   = $stockByWebsiteIdResolver;
        $this->checkItemsQuantity         = $checkItemsQuantity;
        $this->salesChannelFactory        = $salesChannelFactory;

        $websiteCode = $this->websiteRepository->getById($websiteId)->getCode();
        $stockId     = (int)$this->stockByWebsiteIdResolver->execute((int)$websiteId)->getStockId();

        $this->checkItemsQuantity->execute($itemsBySku, $stockId);

        /** @var SalesEventExtensionInterface */
        $salesEventExtension = $this->salesEventExtensionFactory->create(
            [
                'data' => [
                    'objectIncrementId' => (string)$order->getIncrementId()
                ]
            ]
        );

        /** @var SalesEventInterface $salesEvent */
        $salesEvent = $this->salesEventFactory->create(
            [
                'type'       => SalesEventInterface::EVENT_ORDER_PLACED,
                'objectType' => SalesEventInterface::OBJECT_TYPE_ORDER,
                'objectId'   => (string)$order->getEntityId()
            ]
        );
        $salesEvent->setExtensionAttributes($salesEventExtension);
        $salesChannel = $this->salesChannelFactory->create(
            [
                'data' => [
                    'type' => SalesChannelInterface::TYPE_WEBSITE,
                    'code' => $websiteCode
                ]
            ]
        );

        $this->placeReservationsForSalesEvent->execute($itemsToSell, $salesChannel, $salesEvent);

        try {
            $order = $proceed($order);
        } catch (\Exception $e) {
            //add compensation
            foreach ($itemsToSell as $item) {
                $item->setQuantity(-(float)$item->getQuantity());
            }

            /** @var SalesEventInterface $salesEvent */
            $salesEvent = $this->salesEventFactory->create(
                [
                    'type'       => SalesEventInterface::EVENT_ORDER_PLACE_FAILED,
                    'objectType' => SalesEventInterface::OBJECT_TYPE_ORDER,
                    'objectId'   => (string)$order->getEntityId()
                ]
            );
            $salesEvent->setExtensionAttributes($salesEventExtension);

            $this->placeReservationsForSalesEvent->execute($itemsToSell, $salesChannel, $salesEvent);

            throw $e;
        }

        return $order;
    }

    public function aroundPlaceProcessForMagento246AndHigher($order, $itemsBySku, $itemsToSell, $proceed, $websiteId)
    {
        $appendReservations   = $this->objectManager->get(
            AppendReservations::class
        );
        $reservationExecution = $this->objectManager->get(
            ReservationExecutionInterface::class
        );

        $this->appendReservations   = $appendReservations;
        $this->reservationExecution = $reservationExecution;

        if ($this->reservationExecution->isDeferred()) {
            $websiteId = (int)$order->getStore()->getWebsiteId();
            [$salesChannel, $salesEventExtension]
                = $this->appendReservations->reserve($websiteId, $itemsBySku, $order, $itemsToSell);
            $order = $this->createOrder($proceed, $order, $itemsToSell, $salesChannel, $salesEventExtension);
        } else {
            $order = $proceed($order);
        }

        return $order;
    }
}
