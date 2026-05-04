<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionFeatures\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\CatalogInventory\Model\ResourceModel\Stock as StockResource;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Model\Spi\StockRegistryProviderInterface;
use Magento\Framework\Registry;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionFeatures\Model\QtyMultiplier;

class SubtractQtyMultiplierQty implements ObserverInterface
{
    protected StockResource $stockResource;
    protected StockConfigurationInterface $stockConfiguration;
    protected StockRegistryProviderInterface $stockRegistryProvider;
    protected BaseHelper $baseHelper;
    protected QtyMultiplier $qtyMultiplier;
    protected Registry $registry;

    /**
     * SubtractQtyMultiplierQty constructor.
     *
     * @param BaseHelper $baseHelper
     * @param QtyMultiplier $qtyMultiplier
     * @param StockResource $stockResource
     * @param StockRegistryProviderInterface $stockRegistryProvider
     * @param StockConfigurationInterface $stockConfiguration
     * @param Registry $registry
     */
    public function __construct(
        BaseHelper $baseHelper,
        QtyMultiplier $qtyMultiplier,
        StockResource $stockResource,
        StockRegistryProviderInterface $stockRegistryProvider,
        StockConfigurationInterface $stockConfiguration,
        Registry $registry
    ) {
        $this->baseHelper            = $baseHelper;
        $this->qtyMultiplier         = $qtyMultiplier;
        $this->stockResource         = $stockResource;
        $this->stockRegistryProvider = $stockRegistryProvider;
        $this->stockConfiguration    = $stockConfiguration;
        $this->registry              = $registry;
    }

    /**
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        if ($this->baseHelper->isModuleEnabled('Magento_InventorySalesAdminUi')
            && $this->baseHelper->isModuleEnabled('Magento_InventorySalesApi')
        ) {
            return $this;
        }

        $websiteId = $this->stockConfiguration->getDefaultScopeId();

        if (!$this->registry->registry('current_shipment')) {
            return $this;
        }

        $currentItems = $this->registry->registry('current_shipment')->getAllItems();

        foreach ($currentItems as $currentItem) {
            $orderItem               = $currentItem->getOrderItem();
            $qty                     = (float)$currentItem->getQty();
            $currentQtyMultiplierQty = $this->qtyMultiplier->getQtyMultiplierQtyForCurrentItemQty(
                $orderItem,
                $qty
            );
            if (!$currentQtyMultiplierQty) {
                continue;
            }
            $orderItem = $currentItem->getOrderItem();
            $productId = $orderItem->getProduct()->getId();

            $stockItem = $this->stockRegistryProvider->getStockItem(
                $orderItem->getProduct()->getData($this->baseHelper->getLinkField()),
                $websiteId
            );

            if (!$stockItem->getManageStock()) {
                continue;
            }

            $this->stockResource->correctItemsQty(
                [
                    $productId => $currentQtyMultiplierQty - $currentItem->getQty()
                ],
                $websiteId,
                '-'
            );

        }

        return $this;
    }
}
