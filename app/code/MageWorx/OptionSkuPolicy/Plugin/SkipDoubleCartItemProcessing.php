<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionSkuPolicy\Plugin;

use Magento\Catalog\Model\CustomOptions\CustomOptionProcessor;
use Magento\Quote\Api\Data\CartItemInterface;

/**
 * This plugin fix issue when Magento tries to reassign cart items with new buyRequest and deletes the old ones.
 * Preconditions: add item to the cart, go to checkout, use PayPal express checkout as payment method, place order from PayPal,
 * order is placed (you shouldn't be redirected to magento pre-order confirmation page, for example, to define shipping method)
 */
class SkipDoubleCartItemProcessing
{
    /**
     * @param CustomOptionProcessor $subject
     * @param \Closure $proceed
     * @param CartItemInterface $cartItem
     * @return \Magento\Framework\DataObject|null
     */
    public function aroundConvertToBuyRequest(
        CustomOptionProcessor $subject,
        \Closure $proceed,
        CartItemInterface $cartItem
    ) {
        if ($cartItem->getIsSkuPolicyApplied() && $cartItem->getProductType() !== 'configurable') {
            return null;
        }

        return $proceed($cartItem);
    }
}
