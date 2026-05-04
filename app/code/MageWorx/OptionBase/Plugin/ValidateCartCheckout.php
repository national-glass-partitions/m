<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionBase\Plugin;

use Magento\Catalog\Api\Data\ProductCustomOptionInterface;
use MageWorx\OptionBase\Model\ValidationResolver;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\Catalog\Model\Product;

class ValidateCartCheckout
{
    protected BaseHelper $baseHelper;
    protected ValidationResolver $validationResolver;

    public function __construct(
        ValidationResolver $validationResolver,
        BaseHelper $baseHelper
    ) {
        $this->validationResolver = $validationResolver;
        $this->baseHelper = $baseHelper;
    }

    /**
     * Check custom conditions to allow validate options on cart and checkout
     *
     * @param AbstractType $subject
     * @param \Closure $proceed
     * @param \Magento\Catalog\Model\Product $product
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function aroundCheckProductBuyState(AbstractType $subject, \Closure $proceed, $product)
    {
        if (!$product->getSkipCheckRequiredOption() && $product->getHasOptions()) {
            $options = $product->getProductOptionsCollection();
            foreach ($options as $option) {
                if ($option->getIsRequire() && $this->hasValidationPermission($product, $option)) {
                    $customOption = $product->getCustomOption($subject::OPTION_PREFIX . $option->getId());
                    if (!$customOption || strlen($customOption->getValue()) == 0) {
                        $product->setSkipCheckRequiredOption(true);
                        throw new \Magento\Framework\Exception\LocalizedException(
                            __('The product has required options.')
                        );
                    }
                }
            }
        }

        return [$product];
    }

    /**
     * Check validation permission from APO modules
     *
     */
    protected function hasValidationPermission(Product $product, ProductCustomOptionInterface $option): bool
    {
        if (!$this->validationResolver->getValidators()) {
            return true;
        }

        /* @var $validatorItem \MageWorx\OptionBase\Api\ValidatorInterface */
        foreach ($this->validationResolver->getValidators() as $validatorItem) {
            if (!$validatorItem->canValidateCartCheckout($product, $option)) {
                return false;
            }
        }
        return true;
    }
}

