<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionInventory\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use MageWorx\OptionInventory\Model\ResourceModel\Product\CollectionUpdaterStock;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionInventory\Helper\Stock as HelperStock;


class UpdateLinkedValueQtyAfterSaveProduct implements ObserverInterface
{
    protected CollectionUpdaterStock $collectionUpdaterStock;
    protected BaseHelper $baseHelper;
    protected HelperStock $helperStock;

    /**
     * UpdateLinkedValueQtyAfterSaveProduct constructor.
     *
     * @param CollectionUpdaterStock $collectionUpdaterStock
     * @param BaseHelper $baseHelper
     * @param HelperStock $helperStock
     */
    public function __construct(
        CollectionUpdaterStock $collectionUpdaterStock,
        BaseHelper $baseHelper,
        HelperStock $helperStock
    ) {
        $this->collectionUpdaterStock = $collectionUpdaterStock;
        $this->baseHelper             = $baseHelper;
        $this->helperStock            = $helperStock;
    }

    /**
     * link salable qty - temporary solution
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer): void
    {
        if (!$this->helperStock->validateLinkedQtyField()) {
            return;
        }

        $product    = $observer->getProduct();
        $productSku = $product->getSku();

        if (!$product->getHasOptions()
            && $product->getTypeId() !== \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE
        ) {
            $this->collectionUpdaterStock->updateLinkedValueQty(
                $productSku,
                $this->baseHelper->updateValueQtyToSalableQty($productSku)
            );
        }
    }
}
