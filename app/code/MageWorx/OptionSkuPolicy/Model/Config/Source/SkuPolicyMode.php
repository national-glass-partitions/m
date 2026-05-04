<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionSkuPolicy\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use MageWorx\OptionSkuPolicy\Helper\Data as Helper;

class SkuPolicyMode implements ArrayInterface
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
                'value' => Helper::SKU_POLICY_STANDARD,
                'label' => __('Standard')
            ],
            [
                'value' => Helper::SKU_POLICY_DISABLED,
                'label' => __('Disabled')
            ],
            [
                'value' => Helper::SKU_POLICY_REPLACEMENT,
                'label' => __('Replacement')
            ],
            [
                'value' => Helper::SKU_POLICY_INDEPENDENT,
                'label' => __('Independent')
            ],
            [
                'value' => Helper::SKU_POLICY_GROUPED,
                'label' => __('Grouped')
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
            Helper::SKU_POLICY_STANDARD    => __('Standard'),
            Helper::SKU_POLICY_DISABLED    => __('Disabled'),
            Helper::SKU_POLICY_REPLACEMENT => __('Replacement'),
            Helper::SKU_POLICY_INDEPENDENT => __('Independent'),
            Helper::SKU_POLICY_GROUPED     => __('Grouped')
        ];
    }

    /**
     * Get SKU policy options
     *
     * @return array
     */
    public function getOptions()
    {
        return [
            0 => [
                'label' => __('Use Config'),
                'value' => Helper::SKU_POLICY_USE_CONFIG,
            ],
            1 => [
                'label' => __('Standard'),
                'value' => Helper::SKU_POLICY_STANDARD,
            ],
            2 => [
                'label' => __('Disabled'),
                'value' => Helper::SKU_POLICY_DISABLED,
            ],
            3 => [
                'label' => __('Replacement'),
                'value' => Helper::SKU_POLICY_REPLACEMENT,
            ],
            4 => [
                'label' => __('Independent'),
                'value' => Helper::SKU_POLICY_INDEPENDENT,
            ],
            5 => [
                'label' => __('Grouped'),
                'value' => Helper::SKU_POLICY_GROUPED,
            ]
        ];
    }
}
