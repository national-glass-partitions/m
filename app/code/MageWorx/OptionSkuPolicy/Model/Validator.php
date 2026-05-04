<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionSkuPolicy\Model;

use Magento\Catalog\Api\Data\ProductCustomOptionInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Option\Type\DefaultType;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use MageWorx\OptionBase\Api\ValidatorInterface;
use MageWorx\OptionSkuPolicy\Helper\Data as Helper;

class Validator implements ValidatorInterface
{
    protected Helper $helper;
    protected SkuPolicy $skuPolicy;
    protected ProductResource $productResource;

    /**
     * Validator constructor.
     *
     * @param Helper $helper
     * @param SkuPolicy $skuPolicy
     * @param ProductResource $productResource
     */
    public function __construct(
        Helper $helper,
        SkuPolicy $skuPolicy,
        ProductResource $productResource
    ) {
        $this->helper             = $helper;
        $this->skuPolicy          = $skuPolicy;
        $this->productResource    = $productResource;
    }

    /**
     * Run validation process for add to cart action
     *
     */
    public function canValidateAddToCart(DefaultType $subject, array $values): bool
    {
        if ($this->helper->isEnabledSkuPolicy()) {

            $skuPolicy = $this->getSkuPolicy($subject->getProduct(), $subject->getOption());

            if ($this->isSkipValidationProcess($skuPolicy, $values)) {
                $optionSku = $subject->getOption()->getSku();
                if ($optionSku && $this->productResource->getIdBySku($optionSku)) {
                    return false;
                }
            }
        }

        return true;
    }


    /**
     * Whether skip add to cart validation
     *
     */
    protected function isSkipValidationProcess(string $skuPolicy, array $values): bool
    {
        if ($skuPolicy == Helper::SKU_POLICY_INDEPENDENT || $skuPolicy == Helper::SKU_POLICY_GROUPED) {
            if ($this->skuPolicy->getIsSubmitQuoteFlag() || $values) {
                return true;
            }
        }

        return false;
    }

    /**
     * Run validation process for cart and checkout
     *
     */
    public function canValidateCartCheckout(ProductInterface $product, ProductCustomOptionInterface $option): bool
    {
        if (!$this->helper->isEnabledSkuPolicy()) {
            return true;
        }
        $skuPolicy = $this->getSkuPolicy($product, $option);

        if ($skuPolicy == Helper::SKU_POLICY_INDEPENDENT || $skuPolicy == Helper::SKU_POLICY_GROUPED) {
            return false;
        }

        return true;
    }

    /**
     * Get SKU policy for validation
     *
     */
    protected function getSkuPolicy(ProductInterface $product, ProductCustomOptionInterface $option): string
    {
        $skuPolicy = $option->getSkuPolicy();
        if ($skuPolicy == Helper::SKU_POLICY_USE_CONFIG) {
            $productSkuPolicy = $product->getSkuPolicy();
            if ($productSkuPolicy == Helper::SKU_POLICY_USE_CONFIG || empty($productSkuPolicy)) {
                $skuPolicy = $this->helper->getDefaultSkuPolicy();
            } else {
                $skuPolicy = $productSkuPolicy;
            }
        }

        return $skuPolicy;
    }
}
