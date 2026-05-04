<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See https://www.mageworx.com/terms-and-conditions for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionBase\Plugin;

use MageWorx\OptionBase\Model\GeneralConfigReader;
use MageWorx\OptionBase\Model\ClearOptionValueCollectionCache;
use Magento\Catalog\Model\ResourceModel\Product as ProductResourceModel;

class ClearOptionValueCacheByOptionPlugin
{
    protected ProductResourceModel $productResourceModel;
    protected ClearOptionValueCollectionCache $cacheCleaner;
    protected GeneralConfigReader $configReader;

    public function __construct(
        ProductResourceModel $productResourceModel,
        ClearOptionValueCollectionCache $cacheCleaner,
        GeneralConfigReader $configReader
    ) {
        $this->productResourceModel = $productResourceModel;
        $this->cacheCleaner         = $cacheCleaner;
        $this->configReader         = $configReader;
    }

    /**
     * @param \Magento\Catalog\Api\ProductCustomOptionRepositoryInterface $subject
     * @param \Magento\Catalog\Api\Data\ProductCustomOptionInterface $option
     * @return \Magento\Catalog\Api\Data\ProductCustomOptionInterface
     */
    public function afterSave(
        \Magento\Catalog\Api\ProductCustomOptionRepositoryInterface $subject,
        \Magento\Catalog\Api\Data\ProductCustomOptionInterface $option
    ) {
        if ($this->configReader->useOptionValueCollectionCache()) {
            $productId = $this->productResourceModel->getIdBySku($option->getProductSku());

            if ($productId) {
                $this->cacheCleaner->execute([$productId]);
            }
        }

        return $option;
    }
}
