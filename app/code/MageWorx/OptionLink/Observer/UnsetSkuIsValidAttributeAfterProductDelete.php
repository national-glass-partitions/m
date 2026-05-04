<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);


namespace MageWorx\OptionLink\Observer;

use MageWorx\OptionLink\Model\OptionValueAttributeUpdater\SkuIsValidUpdaterProcess as SkuIsValidUpdaterProcess;
use \Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\Event\Observer as EventObserver;

class UnsetSkuIsValidAttributeAfterProductDelete implements ObserverInterface
{
    protected SkuIsValidUpdaterProcess $skuIsValidUpdaterProcess;

    /**
     * UnsetSkuIsValidAttributeAfterProductDelete constructor.
     *
     * @param SkuIsValidUpdaterProcess $skuIsValidUpdaterProcess
     */
    public function __construct(SkuIsValidUpdaterProcess $skuIsValidUpdaterProcess)
    {
        $this->skuIsValidUpdaterProcess = $skuIsValidUpdaterProcess;
    }

    /**
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(EventObserver $observer)
    {
        $productSku = $observer->getProduct()->getSku();
        $this->skuIsValidUpdaterProcess->updateSkuIsValidAttributeData(false, (string)$productSku);

        return $this;
    }
}
