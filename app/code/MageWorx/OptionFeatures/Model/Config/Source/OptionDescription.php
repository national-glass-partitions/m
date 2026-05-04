<?php
/**
 * Copyright © 2017 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionFeatures\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class OptionDescription implements ArrayInterface
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
                'value' => 2,
                'label' => __('Plain Text Beside Option')
            ],
            [
                'value' => 1,
                'label' => __('Tooltip')
            ],
            [
                'value' => 0,
                'label' => __('No')
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
            0 => __('No'),
            1 => __('Tooltip'),
            2 => __('Plain Text Beside Option')
        ];
    }
}
