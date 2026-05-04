<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See https://www.mageworx.com/terms-and-conditions for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionBase\Observer;

use Magento\Catalog\Model\ResourceModel\Product as ProductResourceModel;
use Magento\CatalogImportExport\Model\Import\Product as ImportProduct;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use MageWorx\OptionBase\Model\ClearOptionValueCollectionCache;
use MageWorx\OptionBase\Model\GeneralConfigReader;

class ClearOptionValueCacheByImportObserver implements ObserverInterface
{
    protected ClearOptionValueCollectionCache $cacheCleaner;
    protected ProductResourceModel $productResourceModel;
    protected GeneralConfigReader $configReader;

    public function __construct(
        ClearOptionValueCollectionCache $cacheCleaner,
        ProductResourceModel $productResourceModel,
        GeneralConfigReader $configReader
    ) {
        $this->cacheCleaner         = $cacheCleaner;
        $this->productResourceModel = $productResourceModel;
        $this->configReader         = $configReader;
    }

    public function execute(Observer $observer)
    {
        if ($this->configReader->useOptionValueCollectionCache()) {
            $bunch = $observer->getEvent()->getBunch();

            if (!empty($bunch)) {
                $skus       = array_unique(array_column($bunch, ImportProduct::COL_SKU));
                $productIds = array_values($this->productResourceModel->getProductsIdsBySkus($skus));

                if ($productIds) {
                    $this->cacheCleaner->execute($productIds);
                }
            }
        }
    }
}
