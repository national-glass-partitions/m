<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionBase\Plugin\Api\Product;

use MageWorx\OptionBase\Model\Product\Attributes as MageWorxProductAttributes;

/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
class GetProductAttributes
{
    protected MageWorxProductAttributes $mageWorxProductAttributes;

    /**
     * ProductRepository constructor.
     *
     * @param MageWorxProductAttributes $mageWorxProductAttributes
     */
    public function __construct(
        MageWorxProductAttributes $mageWorxProductAttributes
    ) {
        $this->mageWorxProductAttributes = $mageWorxProductAttributes;
    }

    /**
     * @param \Magento\Catalog\Model\ProductRepository $subject
     * @param mixed $cachedProduct
     * @return mixed
     */
    public function afterGet(\Magento\Catalog\Model\ProductRepository $subject, $cachedProduct)
    {
        $mageWorxProductAttributes = $this->mageWorxProductAttributes->getData();
        $extensionAttributes       = $cachedProduct->getExtensionAttributes();
        foreach ($mageWorxProductAttributes as $attribute) {
            $attributeName = $attribute->getName();
            $extensionAttributes->setData($attributeName, $cachedProduct->getData($attributeName));
        }
        return $cachedProduct;
    }
}
