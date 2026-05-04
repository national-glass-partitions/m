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
 * Class OrderCreditMemo. Refund option values qty when order is Credit Memo.
 */
class RefundQtyWhenOrderCreditMemo implements ObserverInterface
{
    protected RefundQty $refundQtyModel;
    protected \MageWorx\OptionInventory\Helper\Data $helperData;

    /**
     * RefundQtyWhenOrderCreditMemo constructor.
     *
     * @param RefundQty $refundQtyModel
     * @param \MageWorx\OptionInventory\Helper\Data $helperData
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
            $creditmemo = $observer->getEvent()->getCreditmemo();
            $order      = $creditmemo->getOrder();

            $items = $order->getAllItems();
            $creditMemoItems = $creditmemo->getItems();
            foreach ($items as $item) {
                foreach ($creditMemoItems as $creditMemoItem) {
                    if ($creditMemoItem->getOrderItemId() == $item->getItemId()) {
                        $item->setQty($creditMemoItem->getQty());
                    }
                }

            }

            $this->refundQtyModel->refund($items, 'qty_refunded');
        }

        return $this;
    }
}
