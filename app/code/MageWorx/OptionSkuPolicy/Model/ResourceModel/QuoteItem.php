<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionSkuPolicy\Model\ResourceModel;

use Magento\Quote\Model\ResourceModel\Quote\Item;

class QuoteItem extends Item
{
    /**
     * @param int $quoteItemId
     * @param string $newSku
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function updateSku($quoteItemId, $newSku)
    {
        $this->getConnection()->update(
            $this->getMainTable(),
            ['sku' => $newSku],
            "item_id = '" . $quoteItemId . "'"
        );
    }
}