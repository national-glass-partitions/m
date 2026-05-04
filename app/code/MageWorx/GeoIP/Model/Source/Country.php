<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\GeoIP\Model\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Source model for countries
 */
class Country implements ArrayInterface
{
    /**
     * @var \MageWorx\GeoIP\Helper\Info
     */
    protected $geoIpData;

    /**
     * @param \MageWorx\GeoIP\Helper\Info $geoIpData
     */
    public function __construct(\MageWorx\GeoIP\Helper\Info $geoIpData)
    {
        $this->geoIpData = $geoIpData;
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        $options = [];
        $data    = $this->geoIpData->getMaxmindData();
        uasort($data, [$this, 'sortCountry']);
        foreach ($data as $countryId => $countryData) {
            $options[] = [
                'value' => $countryData['value'],
                'label' => $countryData['label']
            ];
        }

        return $options;
    }

    /**
     * @param array $a
     * @param array $b
     * @return int
     */
    protected function sortCountry($a, $b)
    {
        return strcmp($a['label'], $b['label']);
    }

    /**
     * Get options in "key-value" format
     *
     * @return string[]
     */
    public function toArray()
    {
        $_tmpOptions = $this->toOptionArray();
        $_options    = [];
        foreach ($_tmpOptions as $option) {
            $_options[$option['value']] = $option['label'];
        }

        return $_options;
    }
}
