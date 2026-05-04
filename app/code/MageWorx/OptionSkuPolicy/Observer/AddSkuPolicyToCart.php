<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionSkuPolicy\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use MageWorx\OptionSkuPolicy\Helper\Data as Helper;
use MageWorx\OptionSkuPolicy\Model\SkuPolicy;

class AddSkuPolicyToCart implements ObserverInterface
{
    protected Helper $helper;
    protected SkuPolicy $skuPolicyApplier;

    public function __construct(
        Helper $helper,
        SkuPolicy $skuPolicyApplier
    ) {
        $this->helper           = $helper;
        $this->skuPolicyApplier = $skuPolicyApplier;
    }

    /**
     * Add product to quote action
     * Processing: sku policy
     *
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        $quoteItems = $observer->getItems();

        if ($this->out($quoteItems)) {
            return $this;
        }

        $this->skuPolicyApplier->applySkuPolicyToCart($quoteItems);
        return $this;
    }

    /**
     * Check conditions to start applying SKU policy
     *
     * @param array $items
     * @return bool
     */
    protected function out($items)
    {
        if (!$this->helper->isEnabledSkuPolicy()) {
            return true;
        }

        if (!$this->helper->isSkuPolicyAppliedToCartAndOrder()) {
            return true;
        }

        if (!$items) {
            return true;
        }

        return false;
    }
}
