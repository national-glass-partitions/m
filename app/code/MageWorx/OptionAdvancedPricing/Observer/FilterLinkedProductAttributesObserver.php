<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See https://www.mageworx.com/terms-and-conditions for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionAdvancedPricing\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use MageWorx\OptionAdvancedPricing\Model\SpecialPrice;
use MageWorx\OptionAdvancedPricing\Model\TierPrice;

class FilterLinkedProductAttributesObserver implements ObserverInterface
{
    protected \MageWorx\OptionAdvancedPricing\Helper\Data $helper;

    public function __construct(\MageWorx\OptionAdvancedPricing\Helper\Data $helper)
    {
        $this->helper = $helper;
    }

    public function execute(Observer $observer)
    {
        /** @var \Magento\Framework\DataObject $object */
        $object                  = $observer->getEvent()->getObject();
        $storeId                 = $observer->getEvent()->getStoreId();
        $linkedProductAttributes = $object->getLinkedProductAttributes() ?: '';

        $data = [];
        foreach (explode(',', $linkedProductAttributes) as $attribute) {
            if ((!$this->helper->isSpecialPriceEnabled($storeId) && $attribute == SpecialPrice::KEY_SPECIAL_PRICE)
                || (!$this->helper->isTierPriceEnabled($storeId) && $attribute == TierPrice::KEY_TIER_PRICE)
            ) {
                continue;
            }
            $data[] = $attribute;
        }

        $object->setLinkedProductAttributes(implode(',', $data));
    }
}
