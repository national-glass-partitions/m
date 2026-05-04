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

class AddSkuPolicyToOrder implements ObserverInterface
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
        if ($this->out()) {
            return $this;
        }

        $this->skuPolicyApplier->applySkuPolicyToOrder($observer->getQuote(), $observer->getShippingAssignment());
        return $this;
    }

    /**
     * Check conditions to start applying SKU policy
     *
     * @return bool
     */
    protected function out()
    {
        if (!$this->helper->isEnabledSkuPolicy()) {
            return true;
        }

        if ($this->helper->isSkuPolicyAppliedToCartAndOrder()) {
            return true;
        }

        return false;
    }
}
