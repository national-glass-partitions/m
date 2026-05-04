<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionInventory\Observer;

use \Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\Event\Observer as EventObserver;
use MageWorx\OptionInventory\Helper\Data;
use MageWorx\OptionInventory\Model\StockProvider;

/**
 * Class UpdateOptionsMessages.
 * This observer updates options stock message
 */
class UpdateOptionsMessages implements ObserverInterface
{
    protected ?StockProvider $stockProvider = null;
    protected Data $helperData;

    /**
     * UpdateOptionsMessages constructor.
     *
     * @param StockProvider $stockProvider
     */
    public function __construct(
        StockProvider $stockProvider,
        Data $helperData
    ) {
        $this->stockProvider = $stockProvider;
        $this->helperData    = $helperData;
    }

    /**
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        if ($this->helperData->isEnabledOptionInventory()) {
            $configObj = $observer->getEvent()->getData('configObj');
            $options   = $configObj->getData('config');

            if (isset($options['bundleId'])) {
                return;
            }

            $options = $this->stockProvider->updateOptionsStockMessage($options);
            $configObj->setData('config', $options);
        }
    }
}
