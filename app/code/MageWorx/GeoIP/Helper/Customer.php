<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\GeoIP\Helper;

use Magento\Framework\App\Helper\Context;

/**
 * GeoIP CUSTOMER helper
 */
class Customer extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Framework\Stdlib\CookieManagerInterface
     */
    protected $cookieManager;

    /**
     * @var \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
     */
    protected $cookieMetadataFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\HTTP\Header
     */
    protected $httpHeader;

    /**
     * Customer constructor.
     *
     * @param Context $context
     * @param \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager
     * @param \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\HTTP\Header $httpHeader
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\HTTP\Header $httpHeader
    ) {
        parent::__construct($context);
        $this->cookieManager         = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->storeManager          = $storeManager;
        $this->httpHeader            = $httpHeader;
    }

    /**
     * @param $key
     * @param $value
     * @param bool $encode
     * @param bool $httpOnly
     * @return bool
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException
     * @throws \Magento\Framework\Stdlib\Cookie\FailureToSendException
     */
    public function setCookie($key, $value, $encode = true, $httpOnly = true)
    {
        $stores = $this->storeManager->getStores();

        foreach ($stores as $store) {
            $path = rtrim(str_replace('index.php', '', $store->getStorePath()), '/');
            if (!empty($path)) {
                $this->setCookieByPath($key, $value, $path . '/', $httpOnly);
            }
        }

        $this->setCookieByPath($key, $value, '/', $httpOnly);

        return true;
    }

    /**
     * @param string $key
     * @param $value
     * @param string $path
     * @param bool $httpOnly
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException
     * @throws \Magento\Framework\Stdlib\Cookie\FailureToSendException
     */
    public function setCookieByPath(string $key, $value, string $path = '/', $httpOnly = true)
    {
        $metadata = $this->cookieMetadataFactory->createPublicCookieMetadata();

        $metadata
            ->setDurationOneYear()
            ->setDomain($this->httpHeader->getHttpHost())
            ->setPath($path)
            ->setHttpOnly($httpOnly);

        $this->cookieManager->setPublicCookie($key, $value, $metadata);
    }

    /**
     * Return decoded cookie
     *
     * @param string $key
     * @param boolean $decode
     * @return boolean|string
     */
    public function getCookie($key, $decode = false)
    {
        if ($cookieResult = $this->cookieManager->getCookie($key)) {
            return $cookieResult;
        } else {
            return false;
        }
    }

    /**
     * Get customer IP
     *
     * @return string
     */
    public function getCustomerIp()
    {
        if ($testIp = $this->getDebugIp()) { // for debug: paste into 'getDebugIp' country code like 'US','DE','FR','SE'
            return $testIp;
        }

        if ($this->_getRequest()->getServer('HTTP_CLIENT_IP')) {
            $ip = $this->_getRequest()->getServer('HTTP_CLIENT_IP');
        } elseif ($this->_getRequest()->getServer('HTTP_X_FORWARDED_FOR')) {
            $ip = $this->_getRequest()->getServer('HTTP_X_FORWARDED_FOR');
        } else {
            $ip = $this->_getRequest()->getServer('REMOTE_ADDR');
        }

        $ipArr = explode(',', $ip);
        $ip    = $ipArr[count($ipArr) - 1];

        return trim($ip);
    }

    protected function getDebugIp($countryCode = null)
    {
        switch ($countryCode) {
            case 'US':
                return '24.24.24.24';
            case 'FR':
                return '62.147.0.1';
            case 'SE':
                return '81.13.146.205';
            case 'DE':
                return '78.159.112.71';
            default:
                return $countryCode;
        }
    }
}
