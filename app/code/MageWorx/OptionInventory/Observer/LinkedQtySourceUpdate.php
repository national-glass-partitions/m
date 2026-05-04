<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionInventory\Observer;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\ObjectManagerInterface;
use MageWorx\OptionInventory\Helper\Stock as HelperStock;
use MageWorx\OptionInventory\Model\ResourceModel\Product\CollectionUpdaterStock;
use MageWorx\OptionBase\Helper\Data as BaseHelper;

class LinkedQtySourceUpdate implements ObserverInterface
{
    /**
     * Object Manager instance
     *
     * @var ObjectManagerInterface|null
     */
    protected ?ObjectManagerInterface $objectManager = null;
    protected CollectionUpdaterStock $collectionUpdaterStock;
    protected BaseHelper $baseHelper;
    protected HelperStock $helperStock;
    protected ProductRepositoryInterface $productRepository;

    /**
     * LinkedQtySourceUpdate constructor.
     *
     * @param ObjectManagerInterface $objectManager
     * @param CollectionUpdaterStock $collectionUpdaterStock
     * @param BaseHelper $baseHelper
     * @param HelperStock $helperData
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        CollectionUpdaterStock $collectionUpdaterStock,
        BaseHelper $baseHelper,
        HelperStock $helperStock,
        ProductRepositoryInterface $productRepository
    ) {
        $this->objectManager          = $objectManager;
        $this->collectionUpdaterStock = $collectionUpdaterStock;
        $this->baseHelper             = $baseHelper;
        $this->helperStock            = $helperStock;
        $this->productRepository      = $productRepository;
    }

    /**
     * link salable qty - temporary solution
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer): void
    {
        \Magento\Framework\Profiler::start('optionInventory-linkedQtySourceUpdate-execute');

        if (!$this->helperStock->validateLinkedQtyField()) {
            \Magento\Framework\Profiler::stop('optionInventory-linkedQtySourceUpdate-execute');

            return;
        }
        $optionsCollection = $observer->getOptionCollection()->getItems();
        $values            = $observer->getDataToUpdate();
        foreach ($values as $value) {
            if ($value->getSku() && $value->getSkuIsValid()) {
                $option       = $optionsCollection[$value->getOptionId()];
                $originSku    = $this->collectionUpdaterStock->getOriginSku($value->getSku());

                if (empty($originSku)) {
                    $value->setSkuIsValid(false);
                } else {
                    $currentStock = $this->baseHelper->updateValueQtyToSalableQty($originSku);
                    $value->setQty($currentStock);
                }

                if ($option) {
                    $option->addValue($value);
                    $value->setOption($option);
                }
            }
        }

        /* Backend Product
         *
         * Origin code
         *
         * FIRST
         *
         * time - 0.343183 (0.336154)
         * avg - 0.343183 (0.336154)
         * memory - 2,059,552 (2,059,552)
         *
         * SECOND
         *
         * time - 0.156505 (0.155479)
         * avg - 0.156505 (0.155479)
         * memory - 25,248 (24,368)
         *
         * -------------------------
         *
         * Updated code
         *
         * FIRST
         *
         * time - 0.297270 (0.286873)
         * avg - 0.297270 (0.286873)
         * memory - 831,744 (831,744)
         *
         * SECOND
         *
         * time - 0.134080 (0.130609)
         * avg - 0.134080 (0.130609)
         * memory - 24,368 (24,368)
         *
         */
        \Magento\Framework\Profiler::stop('optionInventory-linkedQtySourceUpdate-execute');
    }
}
