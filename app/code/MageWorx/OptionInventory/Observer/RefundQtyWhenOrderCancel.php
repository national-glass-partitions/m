<?php
/**
 * Copyright © 2018 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionInventory\Observer;

use \Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\Event\Observer as EventObserver;
use \MageWorx\OptionInventory\Model\RefundQty;

/**
 * Class OrderCancel. Refund option values qty when order is Cancel.
 */
class RefundQtyWhenOrderCancel implements ObserverInterface
{
    protected RefundQty $refundQtyModel;
    protected \MageWorx\OptionInventory\Helper\Data $helperData;

    /**
     * OrderCancel constructor.
     *
     * @param RefundQty $refundQtyModel
     */
    public function __construct(
        RefundQty $refundQtyModel,
        \MageWorx\OptionInventory\Helper\Data $helperData
    ) {
        $this->refundQtyModel = $refundQtyModel;
        $this->helperData     = $helperData;
    }

    /**
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(EventObserver $observer)
    {
        if ($this->helperData->isEnabledOptionInventory()) {
            $items = $observer->getEvent()->getOrder()->getAllItems();

            $this->refundQtyModel->refund($items, 'qty_canceled');
        }

        return $this;
    }
}
