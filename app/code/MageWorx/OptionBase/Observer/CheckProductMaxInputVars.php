<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionBase\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use MageWorx\OptionBase\Helper\Data as BaseHelper;

class CheckProductMaxInputVars implements ObserverInterface
{
    protected BaseHelper $baseHelper;

    public function __construct(BaseHelper $baseHelper)
    {
        $this->baseHelper = $baseHelper;
    }

    /**
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        // TODO check is it works ?
        $this->baseHelper->checkMaxInputVars();
    }
}
