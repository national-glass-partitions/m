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

class UpdateSkuIsValidAttributeAfterProductSave implements ObserverInterface
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
        $currentProductSku                  = $observer->getProduct()->getSku();
        $origProductSku                     = $observer->getProduct()->getOrigData('sku');
        $productSkuData                     = [];
        $productSkuData[$currentProductSku] = true;

        if ($currentProductSku != $origProductSku) {
            $productSkuData[$origProductSku] = false;
        }
        foreach ($productSkuData as $productSku => $skuIsValid) {
            $this->skuIsValidUpdaterProcess->updateSkuIsValidAttributeData($skuIsValid, (string)$productSku);
        }

        return $this;
    }
}
