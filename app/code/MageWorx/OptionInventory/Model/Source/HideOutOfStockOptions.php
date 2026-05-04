<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionInventory\Model\Source;

/**
 * Class HideOutOfStockOptions
 * @package MageWorx\OptionInventory\Model\Source
 */
class HideOutOfStockOptions
{
    /**
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 0, 'label' => __('Hide')],
            ['value' => 1, 'label' => __('Disable')],
        ];
    }
}
