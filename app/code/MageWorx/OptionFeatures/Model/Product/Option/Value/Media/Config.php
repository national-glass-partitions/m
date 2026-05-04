<?php
/**
 * Copyright © 2017 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionFeatures\Model\Product\Option\Value\Media;

use Magento\Catalog\Model\Product\Media\ConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

class Config implements ConfigInterface
{
    /**
     * Store manager
     *
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;

    public function __construct(StoreManagerInterface $storeManager)
    {
        $this->storeManager = $storeManager;
    }

    /**
     * @param string $file
     * @return string
     */
    public function getMediaPath($file): string
    {
        return $this->getBaseMediaPath() . '/' . $this->prepareFile($file);
    }

    /**
     * Filesystem directory path of option value images
     * relatively to media folder
     *
     * @return string
     */
    public function getBaseMediaPath()
    {
        return 'mageworx/optionfeatures/product/option/value';
    }

    /**
     * @param string $file
     * @return string
     */
    protected function prepareFile($file): string
    {
        return ltrim(str_replace('\\', '/', (string)$file), '/');
    }

    /**
     * @param string $file
     * @return string
     */
    public function getMediaUrl($file): string
    {
        return $this->getBaseMediaUrl() . '/' . $this->prepareFile($file);
    }

    /**
     * @return string
     */
    public function getBaseMediaUrl()
    {
        return (string)$this->storeManager->getStore()->getBaseUrl(
            UrlInterface::URL_TYPE_MEDIA
        ) . $this->getBaseMediaPath();
    }

    /**
     * @param string $file
     * @return string
     */
    public function getUrl($file): string
    {
        return rtrim($this->getBaseUrl(), '/') . '/' . ltrim($this->prepareFile($file), '/');
    }

    /**
     * @return string
     */
    public function getBaseUrl()
    {
        return (string)$this->storeManager->getStore()->getBaseUrl(
            UrlInterface::URL_TYPE_MEDIA
        );
    }
}
