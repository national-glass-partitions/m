<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Catalog\Model\View\Asset;

use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Config\CatalogMediaConfig;
use Magento\Catalog\Model\Product\Image\ConvertImageMiscParamsToReadableFormat;
use Magento\Catalog\Model\Product\Media\ConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Asset\ContextInterface;
use Magento\Framework\View\Asset\LocalInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * A locally available image file asset that can be referred with a file path
 *
 * This class is a value object with lazy loading of some of its data (content, physical file path)
 */
class Image implements LocalInterface
{
    /**
     * Image type of image (thumbnail,small_image,image,swatch_image,swatch_thumb)
     *
     * @var string
     */
    private $sourceContentType;

    /**
     * @var string
     */
    private $filePath;

    /**
     * @var string
     */
    private $contentType = 'image';

    /**
     * @var ContextInterface
     */
    private $context;

    /**
     * Misc image params depend on size, transparency, quality, watermark etc.
     *
     * @var array
     */
    private $miscParams;

    /**
     * @var ConfigInterface
     */
    private $mediaConfig;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var ImageHelper
     */
    private $imageHelper;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var string
     */
    private $mediaFormatUrl;

    /**
     * @var ConvertImageMiscParamsToReadableFormat
     */
    private $convertImageMiscParamsToReadableFormat;

    /**
     * Image constructor.
     *
     * @param ConfigInterface $mediaConfig
     * @param ContextInterface $context
     * @param EncryptorInterface $encryptor
     * @param string $filePath
     * @param array $miscParams
     * @param ImageHelper $imageHelper
     * @param CatalogMediaConfig $catalogMediaConfig
     * @param StoreManagerInterface $storeManager
     * @param ConvertImageMiscParamsToReadableFormat $convertImageMiscParamsToReadableFormat
     */
    public function __construct(
        ConfigInterface $mediaConfig,
        ContextInterface $context,
        EncryptorInterface $encryptor,
        $filePath,
        array $miscParams,
        ImageHelper $imageHelper = null,
        CatalogMediaConfig $catalogMediaConfig = null,
        StoreManagerInterface $storeManager = null,
        ?ConvertImageMiscParamsToReadableFormat $convertImageMiscParamsToReadableFormat = null
    ) {
        if (isset($miscParams['image_type'])) {
            $this->sourceContentType = $miscParams['image_type'];
            unset($miscParams['image_type']);
        } else {
            $this->sourceContentType = $this->contentType;
        }
        $this->mediaConfig = $mediaConfig;
        $this->context = $context;
        $this->filePath = $filePath;
        $this->miscParams = $miscParams;
        $this->encryptor = $encryptor;
        $this->imageHelper = $imageHelper ?: ObjectManager::getInstance()->get(ImageHelper::class);
        $this->storeManager = $storeManager ?: ObjectManager::getInstance()->get(StoreManagerInterface::class);

        $catalogMediaConfig =  $catalogMediaConfig ?: ObjectManager::getInstance()->get(CatalogMediaConfig::class);
        $this->mediaFormatUrl = $catalogMediaConfig->getMediaUrlFormat();
        $this->convertImageMiscParamsToReadableFormat = $convertImageMiscParamsToReadableFormat ?:
            ObjectManager::getInstance()->get(ConvertImageMiscParamsToReadableFormat::class);
    }

    /**
     * Get catalog image URL.
     *
     * @return string
     * @throws LocalizedException
     */
    public function getUrl()
    {
        switch ($this->mediaFormatUrl) {
            case CatalogMediaConfig::IMAGE_OPTIMIZATION_PARAMETERS:
                return $this->getUrlWithTransformationParameters();
            case CatalogMediaConfig::HASH:
				# 2026-05-04 Dmitrii Fediuk https://upwork.com/fl/mage2pro
				# 1) "Adapt the website to Windows-based servers":
				# https://github.com/nationalglasspartitions/m/issues/2
				# 2.1) "How to fix URLs of catalog images in Windows for Magento ≥ 2.3.0?":
				# https://mage2.pro/t/6413
				# 2.2) "How to adapt `Magento\Catalog\Model\View\Asset\Image::getUrl()` to Windows
				# in Magento ≥ 2.4.2?" https://mage2.pro/t/6411
				# 3.1) "The `Swissup_Pagespeed` module breaks URLs of images in Windows":
				# https://github.com/mydreamday-fi/site/issues/19
				# 3.2) "The `Swissup_Pagespeed` module breaks URLs of products' images in Windows":
				# https://github.com/mydreamday-fi/site/issues/18
				# 3.3) "The `Swissup_Pagespeed` module breaks URLs of categories' images in Windows":
				# https://github.com/mydreamday-fi/site/issues/17
                return $this->context->getBaseUrl() . '/' . $this->getImageInfo();
            default:
                throw new LocalizedException(
                    __("The specified Catalog media URL format '$this->mediaFormatUrl' is not supported.")
                );
        }
    }

    /**
     * Get image URL with transformation parameters
     *
     * @return string
     */
    private function getUrlWithTransformationParameters()
    {
        return $this->getOriginalImageUrl() . '?' . http_build_query($this->getImageTransformationParameters());
    }

    /**
     * The list of parameters to be used during image transformations (e.g. resizing or applying watermarks).
     *
     * This method can be used as an extension point.
     *
     * @return string[]
     */
    public function getImageTransformationParameters()
    {
        return [
            'width' => $this->miscParams['image_width'],
            'height' => $this->miscParams['image_height'],
            'store' => $this->storeManager->getStore()->getCode(),
            'image-type' => $this->sourceContentType
        ];
    }

    /**
     * Get URL to the original version of the product image.
     *
     * @return string
     */
    private function getOriginalImageUrl()
    {
        $originalImageFile = $this->getSourceFile();
        if (!$originalImageFile) {
            return $this->imageHelper->getDefaultPlaceholderUrl();
        } else {
            return $this->context->getBaseUrl() . $this->getFilePath();
        }
    }

    /**
     * @inheritdoc
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * @inheritdoc
     */
    public function getPath()
    {
        return $this->context->getPath() . DIRECTORY_SEPARATOR . $this->getImageInfo();
    }

    /**
     * @inheritdoc
     */
    public function getSourceFile()
    {
        $path = $this->getFilePath() ? ltrim($this->getFilePath(), DIRECTORY_SEPARATOR) : '';
        return $this->mediaConfig->getBaseMediaPath() . DIRECTORY_SEPARATOR . $path;
    }

    /**
     * Get source content type
     *
     * @return string
     */
    public function getSourceContentType()
    {
        return $this->sourceContentType;
    }

    /**
     * @inheritdoc
     */
    public function getContent()
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    /**
     * @inheritdoc
     *
     * @return ContextInterface
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @inheritdoc
     */
    public function getModule()
    {
        return 'cache';
    }

    /**
     * Retrieve part of path based on misc params
     *
     * @return string
     */
    private function getMiscPath()
    {
        return $this->encryptor->hash(
            implode('_', $this->convertToReadableFormat($this->miscParams)),
            Encryptor::HASH_VERSION_MD5
        );
    }

    /**
     * Generate path from image info
     *
     * @return string
     */
    private function getImageInfo()
    {
        $path = $this->getModule()
            . DIRECTORY_SEPARATOR . $this->getMiscPath()
            . DIRECTORY_SEPARATOR . $this->getFilePath();
        return preg_replace('|\Q'. DIRECTORY_SEPARATOR . '\E+|', DIRECTORY_SEPARATOR, $path);
    }

    /**
     * Converting bool into a string representation
     *
     * @param array $miscParams
     * @return array
     */
    private function convertToReadableFormat(array $miscParams)
    {
        return $this->convertImageMiscParamsToReadableFormat->convertImageMiscParamsToReadableFormat($miscParams);
    }
}
