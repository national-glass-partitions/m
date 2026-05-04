<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionTemplates\Model;

use MageWorx\OptionBase\Model\Product\Attributes as ProductAttributesEntity;

class ProductAttributes
{
    /**
     * @var ProductAttributesEntity
     */
    protected $productAttributes;

    /**
     * @param ProductAttributesEntity $productAttributes
     */
    public function __construct(
        ProductAttributesEntity $productAttributes
    ) {
        $this->productAttributes = $productAttributes;
    }

    /**
     * Get product attributes from group
     *
     * @param Group $group
     * @return array
     */
    public function getProductAttributesFromGroup($group)
    {
        $attributes = [];
        $productAttributes = $this->productAttributes->getData();
            /** @var \MageWorx\OptionBase\Api\ProductAttributeInterface $productAttribute */
        foreach ($productAttributes as $productAttribute) {
            $attributes[$productAttribute->getName()] = $group->getData($productAttribute->getName());
        }
        return $attributes;
    }
}
