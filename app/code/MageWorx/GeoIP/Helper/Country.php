<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\GeoIP\Helper;

/**
 * GeoIP COUNTRY helper
 */
class Country
{

    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $assetRepo;

    /**
     * @param \Magento\Framework\View\Asset\Repository $assetRepo
     */
    public function __construct(
        \Magento\Framework\View\Asset\Repository $assetRepo
    ) {
        $this->assetRepo = $assetRepo;
    }

    /**
     * Return path to country flag
     *
     * @param string $name
     * @return string
     */
    public function getFlagPath(?string $name = null): string
    {
        $flagName = strtolower($name) . '.png';

        return $this->assetRepo->getUrl('MageWorx_GeoIP::images/flags/' . $flagName);
    }

    /**
     * Convert country code to upper case
     *
     * @param string $countryCode
     * @return string
     */
    public function prepareCode(string $countryCode): string
    {
        return strtoupper(trim((string)$countryCode));
    }
}
