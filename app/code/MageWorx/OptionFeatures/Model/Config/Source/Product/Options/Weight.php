<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionFeatures\Model\Config\Source\Product\Options;

/**
 * Weight types mode source
 *
 */
class Weight
{
    const VALUE_FIXED   = 'fixed';
    const VALUE_PERCENT = 'percent';

    /**
     * Store manager.
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private \Magento\Store\Model\StoreManagerInterface $storeManager;

    public function __construct(\Magento\Store\Model\StoreManagerInterface $storeManager)
    {
        $this->storeManager = $storeManager;
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::VALUE_FIXED, 'label' => __('Fixed')],
            ['value' => self::VALUE_PERCENT, 'label' => __('Percent')],
        ];
    }

    /**
     * Get option array of prefixes.
     *
     * @param $unit
     * @return array
     */
    public function prefixesToOptionArray($unit)
    {
        return [
            ['value' => self::VALUE_FIXED, 'label' => $unit],
            ['value' => self::VALUE_PERCENT, 'label' => '%'],
        ];
    }

}
