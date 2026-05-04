<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionFeatures\Model\Config\Source;

use \Magento\Framework\Data\OptionSourceInterface;
use MageWorx\OptionFeatures\Helper\Data as Helper;

class ShareableLinkMode implements OptionSourceInterface
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
                'value' => Helper::SHAREABLE_LINK_USE_CONFIG,
                'label' => __('Use Config')
            ],
            [
                'value' => Helper::SHAREABLE_LINK_ENABLED,
                'label' => __('Enabled')
            ],
            [
                'value' => Helper::SHAREABLE_LINK_DISABLED,
                'label' => __('Disabled')
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
            Helper::SHAREABLE_LINK_USE_CONFIG => __('Use Config'),
            Helper::SHAREABLE_LINK_ENABLED    => __('Enabled'),
            Helper::SHAREABLE_LINK_DISABLED   => __('Disabled')
        ];
    }

    /**
     * Get Shareable Link options
     *
     * @return array
     */
    public function getOptions()
    {
        return [
            0 => [
                'label' => __('Use Config'),
                'value' => Helper::SHAREABLE_LINK_USE_CONFIG,
            ],
            1 => [
                'label' => __('Enabled'),
                'value' => Helper::SHAREABLE_LINK_ENABLED,
            ],
            2 => [
                'label' => __('Disabled'),
                'value' => Helper::SHAREABLE_LINK_DISABLED,
            ]
        ];
    }
}
