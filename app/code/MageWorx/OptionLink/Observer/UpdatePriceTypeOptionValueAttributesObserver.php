<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See https://www.mageworx.com/terms-and-conditions for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionLink\Observer;

use Magento\Catalog\Model\Indexer\Product\Price\Processor;
use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use MageWorx\OptionLink\Helper\Data as HelperData;
use MageWorx\OptionLink\Model\OptionValueAttributeUpdaterInterface;
use MageWorx\OptionLink\Model\ResourceModel\Product\Option\Value\LinkedAttribute as LinkedAttributeResource;

class UpdatePriceTypeOptionValueAttributesObserver implements ObserverInterface
{
    protected HelperData $helperData;
    protected Processor $priceIndexProcessor;
    protected LinkedAttributeResource $linkedAttributeResource;
    protected array $attributeUpdaters;

    public function __construct(
        HelperData $helperData,
        Processor $priceIndexProcessor,
        LinkedAttributeResource $linkedAttributeResource,
        array $attributeUpdaters = []
    ) {
        $this->helperData              = $helperData;
        $this->priceIndexProcessor     = $priceIndexProcessor;
        $this->linkedAttributeResource = $linkedAttributeResource;
        $this->attributeUpdaters       = $attributeUpdaters;
    }

    /**
     * @param Observer $observer
     * @throws \Exception
     */
    public function execute(Observer $observer)
    {
        /** @var Product $product */
        $product       = $observer->getEvent()->getProduct();
        $optionTypeIds = $this->linkedAttributeResource->getOptionTypeIdsBySku($product->getSku());

        if (empty($optionTypeIds)) {
            return;
        }

        $productAttributes = $this->helperData->getLinkedProductAttributesAsArray();
        $needReindex       = false;

        /** @var OptionValueAttributeUpdaterInterface $attributeUpdater */
        foreach ($this->attributeUpdaters as $attribute => $attributeUpdater) {
            if (in_array($attribute, $productAttributes)) {
                $status = $attributeUpdater->process($optionTypeIds, $product);

                if ($status) {
                    $needReindex = true;
                }
            }
        }

        if ($needReindex) {
            $productIds = $this->linkedAttributeResource->getProductIdsBySku($product->getSku());

            if ($productIds) {
                $this->priceIndexProcessor->reindexList($productIds);
            }
        }
    }
}
