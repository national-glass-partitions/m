<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionSkuPolicy\Plugin;

use Magento\Quote\Model\Quote\Item as Item;
use MageWorx\OptionSkuPolicy\Helper\Data as Helper;
use MageWorx\OptionSkuPolicy\Model\SkuPolicy;

class ValidateAdminMoveQuoteItem
{
    protected Helper $helper;
    protected SkuPolicy $skuPolicy;

    public function __construct(
        Helper    $helper,
        SkuPolicy $skuPolicy
    ) {
        $this->helper    = $helper;
        $this->skuPolicy = $skuPolicy;
    }

    /**
     * @param $subject
     * @param Item $item
     * @param $moveTo
     * @param $qty
     * @return $this
     */
    public function beforeMoveQuoteItem($subject, Item $item, $moveTo, $qty)
    {
        if (!$this->helper->isEnabledSkuPolicy()) {
            return;
        }

        $moveTo = explode('_', $moveTo);

        if ($moveTo[0] == 'cart') {
            $this->skuPolicy->setIsSubmitQuoteFlag(true);
        }
    }
}
