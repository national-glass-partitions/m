<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionSkuPolicy\Plugin;

use Magento\Catalog\Helper\Product as ProductHelper;
use Magento\Quote\Model\QuoteManagement;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionSkuPolicy\Helper\Data as Helper;
use Magento\Checkout\Model\Cart;

class AddSkuPolicyToOrder
{
    protected Helper $helper;
    protected BaseHelper $baseHelper;
    protected Cart $cart;
    protected ProductHelper $productHelper;

    /**
     * @param ProductHelper $productHelper
     * @param BaseHelper $baseHelper
     * @param Helper $helper
     * @param Cart $cart
     */
    public function __construct(
        Helper $helper,
        BaseHelper $baseHelper,
        ProductHelper $productHelper,
        Cart $cart
    ) {
        $this->productHelper = $productHelper;
        $this->helper        = $helper;
        $this->baseHelper    = $baseHelper;
        $this->cart          = $cart;
    }

    /**
     * Allow to apply SKU Policy to quote before order submit
     *
     * @param QuoteManagement $subject
     * @param \Magento\Quote\Model\Quote $quote
     * @param array $orderData
     * @return \Magento\Framework\Model\AbstractExtensibleModel|\Magento\Sales\Api\Data\OrderInterface|object|null
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function beforeSubmit($subject, $quote, $orderData = [])
    {
        if ($quote->getAllVisibleItems() && !$this->helper->isSkuPolicyAppliedToCartAndOrder()) {
            $quote->setCanApplySkuPolicyToOrder(true);
            $quote->setTotalsCollectedFlag(false);
            $quote->setIsSuperMode(true);
            $this->productHelper->setSkipSaleableCheck(true);
            if ($this->baseHelper->getFullActionName() === 'sales_order_create_save'
                || $this->baseHelper->getFullActionName() === '__'
                || $this->baseHelper->getFullActionName() === 'paypal_express_onAuthorization')
            {
                $quote->collectTotals();
            } else {
                $this->cart->save();
            }
        }

        return [$quote, $orderData];
    }
}
