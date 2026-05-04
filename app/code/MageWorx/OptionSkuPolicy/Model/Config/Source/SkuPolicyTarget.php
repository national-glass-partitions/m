<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionSkuPolicy\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class SkuPolicyTarget implements ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 0,
                'label' => __('Cart and Order')
            ],
            [
                'value' => 1,
                'label' => __('Order')
            ]
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            0 => __('Cart and Order'),
            1 => __('Order')
        ];
    }
}
