<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionSkuPolicy\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use MageWorx\OptionSkuPolicy\Helper\Data as Helper;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionSkuPolicy\Model\ResourceModel\QuoteItem as ResourceModel;

class ApplyCustomSkuToQuoteItem implements ObserverInterface
{
    protected Helper $helper;
    protected BaseHelper $baseHelper;
    protected ResourceModel $resourceModel;

    public function __construct(
        Helper $helper,
        BaseHelper $baseHelper,
        ResourceModel $resourceModel
    ) {
        $this->helper        = $helper;
        $this->baseHelper    = $baseHelper;
        $this->resourceModel = $resourceModel;
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
        if (!$this->helper->isSkuPolicyAppliedToCartAndOrder()) {
            return $this;
        }

        $product    = $observer->getProduct();
        $quoteItem  = $observer->getQuoteItem();
        $buyRequest = $this->baseHelper->getInfoBuyRequest($product);

        if (!empty($buyRequest['sku_policy_sku'])) {
            $quoteItem->setSku($buyRequest['sku_policy_sku']);
            $this->resourceModel->updateSku($quoteItem->getId(), $buyRequest['sku_policy_sku']);
        }
        if (!empty($buyRequest['sku_policy_weight'])) {
            $quoteItem->setWeight($buyRequest['sku_policy_weight']);
        }
        if (!empty($buyRequest['sku_policy_cost'])) {
            $quoteItem->setCost($buyRequest['sku_policy_cost']);
        }
    }
}
