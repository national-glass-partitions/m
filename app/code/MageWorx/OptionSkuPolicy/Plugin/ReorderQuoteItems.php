<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionSkuPolicy\Plugin;

use Magento\Quote\Model\Quote;
use MageWorx\OptionSkuPolicy\Helper\Data as Helper;

/**
 * Reorder quote items.
 * The custom options selected on each product that have independent/grouped SKU Policy should ends up below the main product,
 * instead of grouping in the bottom of the cart/order.
 */
class ReorderQuoteItems
{
    protected Helper $helper;

    public function __construct(
        Helper $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * Retrieve quote items in right order
     *
     * @param Quote $subject
     * @param array $items
     * @return array
     */
    public function afterGetAllItems($subject, $items)
    {
        if (!$subject->getCanChangeQuoteItemsOrder() || !$this->helper->isSplitIndependents()) {
            return $items;
        }

        $map = [];

        foreach ($items as $item) {
            $itemProduct = $item->getProduct();
            $itemProductOptions = $itemProduct->getData('options');
            if (!$itemProductOptions) {
                continue;
            }
            foreach ($itemProductOptions as $itemProductOption) {
                $map[$itemProductOption->getOptionId()] = $item->getId();
            }
        }

        $processedItems = [];
        foreach ($items as $item) {
            $itemProduct = $item->getProduct();
            $parentCustomOptionId = $itemProduct->getCustomOption('parent_custom_option_id');
            if (!$parentCustomOptionId) {
                $processedItems[$item->getId()][] = $item;
            } else {
                if (isset($map[$parentCustomOptionId->getValue()])) {
                    $processedItems[$map[$parentCustomOptionId->getValue()]][] = $item;
                } else {
                    $processedItems[$item->getId()][] = $item;
                }
            }
        }

        $resultItems = [];
        foreach ($processedItems as $processedItem) {
            if (!$processedItem || !is_array($processedItem)) {
                continue;
            }
            $resultItems = array_merge($resultItems, $processedItem);
        }
        return $resultItems;
    }
}
