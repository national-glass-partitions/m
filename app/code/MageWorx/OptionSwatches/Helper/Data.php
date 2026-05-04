<?php
/**
 * Copyright © 2017 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionSwatches\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    const KEY_IS_SWATCH = 'is_swatch';
    const IS_SWATCH_TRUE = '1';
    const IS_SWATCH_FALSE = '0';

    /**
     * Path for redirect to cart
     */
    const XML_PATH_REDIRECT_TO_CART = 'checkout/cart/redirect_to_cart';

    /**
     * Checks if customer should be redirected to shopping cart after adding a product
     *
     * @param int|string|\Magento\Store\Model\Store $store
     * @return bool
     */
    public function isEnabledRedirectToCart($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_REDIRECT_TO_CART,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }
}
