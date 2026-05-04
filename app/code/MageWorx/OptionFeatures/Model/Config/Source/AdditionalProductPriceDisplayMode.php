<?php
/**
 * Copyright ©  MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionFeatures\Model\Config\Source;

class AdditionalProductPriceDisplayMode implements \Magento\Framework\Data\OptionSourceInterface
{
    const PRODUCT_PRICE_DISPLAY_MODE_PER_ITEM    = 'per_item';
    const PRODUCT_PRICE_DISPLAY_MODE_FINAL_PRICE = 'final_price';

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => static::PRODUCT_PRICE_DISPLAY_MODE_PER_ITEM,
                'label' => __('Per Item')
            ],
            [
                'value' => static::PRODUCT_PRICE_DISPLAY_MODE_FINAL_PRICE,
                'label' => __('Final Price')
            ]
        ];
    }
}
