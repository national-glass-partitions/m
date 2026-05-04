<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionSkuPolicy\Model\Attribute\Product;

use MageWorx\OptionSkuPolicy\Helper\Data as Helper;
use MageWorx\OptionBase\Model\Product\AbstractProductAttribute;
use MageWorx\OptionBase\Api\ProductAttributeInterface;

class SkuPolicy extends AbstractProductAttribute implements ProductAttributeInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return Helper::KEY_SKU_POLICY;
    }

    /**
     * Get default value of attribute
     *
     * @return string
     */
    public function getDefaultValue()
    {
        return Helper::SKU_POLICY_USE_CONFIG;
    }

    /**
     * Prepare Magento 1 product attributes for import
     *
     * @param array $productAttributesData
     * @param array $data
     * @return void
     */
    public function prepareOptionsMageOne(&$productAttributesData, $data)
    {
        if (!is_array($data)) {
            return;
        }

        $map = [
            '0' => Helper::SKU_POLICY_USE_CONFIG,
            '1' => Helper::SKU_POLICY_STANDARD,
            '2' => Helper::SKU_POLICY_INDEPENDENT,
            '3' => Helper::SKU_POLICY_GROUPED,
            '4' => Helper::SKU_POLICY_REPLACEMENT,
        ];

        foreach ($data as $sku => $product) {
            $productData = [];

            $attributeKey = $this->getName();
            if (!isset($product['_' . $attributeKey])) {
                continue;
            }
            $productData[$attributeKey] = $map[$product['_' . $attributeKey]];

            if (isset($productAttributesData[$sku])) {
                $productAttributesData[$sku] = array_merge($productAttributesData[$sku], $productData);
            } else {
                $productAttributesData[$sku] = $productData;
            }
        }
    }
}
