<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionSkuPolicy\Plugin;

use Magento\Quote\Model\Quote;

/**
 * This plugin fix fetching quote item by id:
 * Quote item without id (not yet saved) can be added to the collection.
 * Even after being saved, it's still not possible to fetch it $this->getItemsCollection()->getItemById($itemId);
 * as it was added before having the id.
 */
class FixMagentoAddToCart
{
    /**
     * @param Quote $subject
     * @param \Closure $proceed
     * @param int $itemId
     * @return \Magento\Quote\Model\Quote\Item|false
     */
    public function aroundGetItemById(Quote $subject, \Closure $proceed, $itemId)
    {
        foreach ($subject->getItemsCollection() as $item) {
            if ($item->getId() == $itemId) {
                return $item;
            }
        }

        return false;
    }
}
