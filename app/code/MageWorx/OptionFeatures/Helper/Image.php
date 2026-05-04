<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionFeatures\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteFactory;
use Magento\MediaStorage\Model\File\Uploader;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\Image\AdapterFactory;
use MageWorx\OptionFeatures\Model\Product\Option\Value\Media\Config;

class Image extends AbstractHelper
{
    protected Config $mediaConfig;
    protected Filesystem $filesystem;
    protected WriteFactory $directoryWriteFactory;
    protected UploaderFactory $uploaderFactory;
    protected AdapterFactory $adapterFactory;

    public function __construct(
        Context $context,
        Config $mediaConfig,
        Filesystem $filesystem,
        WriteFactory $directoryWriteFactory,
        UploaderFactory $uploaderFactory,
        AdapterFactory $adapterFactory
    ) {
        parent::__construct($context);
        $this->mediaConfig           = $mediaConfig;
        $this->filesystem            = $filesystem;
        $this->directoryWriteFactory = $directoryWriteFactory;
        $this->uploaderFactory       = $uploaderFactory;
        $this->adapterFactory        = $adapterFactory;
    }

    /**
     * Create color image based on hex
     *
     * @param string $hex
     * @return array $result
     */
    public function createColorFile($hex)
    {
        $mediaDirectory    = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
        $filename          = $hex . '.jpg';
        $fileNameCorrected = Uploader::getCorrectFileName($filename);
        $filePath          = Uploader::getDispretionPath($filename) .
            DIRECTORY_SEPARATOR .
            $fileNameCorrected;
        $absolutePatToFile = $mediaDirectory->getAbsolutePath($this->mediaConfig->getBaseMediaPath());
        $path              = $absolutePatToFile . $filePath;

        $image = imagecreatetruecolor(400, 400);
        list($r, $g, $b) = sscanf($hex, "%02x%02x%02x");
        $color = imagecolorallocate($image, $r, $g, $b);
        imagealphablending($image, true);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, $color);

        $dirPath = str_ireplace($fileNameCorrected, '', $path);
        /** @var \Magento\Framework\Filesystem\Directory\Write $directoryWrite */
        $this->directoryWriteFactory->create($dirPath);
        if (!file_exists($dirPath) && !is_dir($dirPath)) {
            mkdir($dirPath, 0777, true);
        }
        imagejpeg($image, $path);

        $result = [
            'name'              => $fileNameCorrected,
            'type'              => mime_content_type($path),
            'error'             => 0,
            'size'              => filesize($path),
            'file'              => $filePath,
            'url'               => $this->mediaConfig->getMediaUrl($filePath),
            'custom_media_type' => 'color',
            'color'             => $hex,
        ];

        return $result;
    }

    /**
     * Create image based on upload action
     *
     * @param object $action
     * @return array $result
     */
    public function createImageFile($action)
    {
        /** @var Uploader $uploader */
        $uploader = $this->uploaderFactory->create(['fileId' => 'image']);
        $uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png']);
        /** @var \Magento\Framework\Image\Adapter\AdapterInterface $imageAdapter */
        $imageAdapter = $this->adapterFactory->create();
        $uploader->addValidateCallback('catalog_product_image', $imageAdapter, 'validateUploadFile');
        $uploader->setAllowRenameFiles(true);
        $uploader->setFilesDispersion(true);
        /** @var \Magento\Framework\Filesystem\Directory\Read $mediaDirectory */
        $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
        $result         = $uploader->save(
            $mediaDirectory->getAbsolutePath($this->mediaConfig->getBaseMediaPath())
        );

        $this->_eventManager->dispatch(
            'mageworx_optionfeatures_upload_image_after',
            ['result' => $result, 'action' => $action]
        );

        unset($result['tmp_name']);
        unset($result['path']);

        $result['url']               = $this->mediaConfig->getMediaUrl($result['file']);
        $result['custom_media_type'] = 'image';

        return $result;
    }
}
