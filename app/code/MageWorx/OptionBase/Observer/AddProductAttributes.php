<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionBase\Observer;

use Magento\Catalog\Model\Product;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use MageWorx\OptionBase\Model\ProductAttributes as ProductAttributesEntity;
use MageWorx\OptionBase\Model\Product\Attributes as ProductAttributes;

class AddProductAttributes implements ObserverInterface
{
    protected ProductAttributes $productAttributes;
    protected ProductAttributesEntity $productAttributesEntity;

    public function __construct(
        ProductAttributes $productAttributes,
        ProductAttributesEntity $productAttributesEntity
    ) {
        $this->productAttributes       = $productAttributes;
        $this->productAttributesEntity = $productAttributesEntity;
    }

    /**
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(EventObserver $observer)
    {
        $product = $observer->getData('product');
        if (!$product || !$product instanceof Product) {
            return $this;
        }

        $item       = $this->productAttributesEntity->getItemByProduct($product);
        $attributes = $this->productAttributes->getData();
        /** @var \MageWorx\OptionBase\Api\ProductAttributeInterface $attribute */
        foreach ($attributes as $attribute) {
            $product[$attribute->getName()] = $item[$attribute->getName()] ?? $attribute->getDefaultValue();
        }

        return $this;
    }
}
