<?php
/**
 * Scommerce Mage - Plugin to intercept upload category file function
 *
 * @category   Scommerce
 * @package    Scommerce_ImageOptimiser
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */

namespace Scommerce\ImageOptimiser\Plugin\Catalog\Model\ImageUploader;

use Magento\Catalog\Model\ImageUploader as ImageUpload;
use Scommerce\ImageOptimiser\Model\Download;
use Scommerce\ImageOptimiser\Helper\Data;
use Magento\Framework\App\Filesystem\DirectoryList;
use Scommerce\ImageOptimiser\Model\Config\Source\Pages;
use Magento\Framework\App\ProductMetadataInterface;

/**
 * Class Storage
* @package Scommerce\ImageOpimiser\Plugin
 */
class ImageUploaderPlugin extends ImageUpload{

    /**
     * @var Download
     */
    protected $_download;
    
    /**
     * @var CoreHelper
     */
    protected $_dataHelper;
    
    /**
     * @var DirectoryList
     */
    protected $_directoryList;
    
    /**
     * Core file storage database
     *
     * @var \Magento\MediaStorage\Helper\File\Storage\Database
     */
    protected $coreFileStorageDatabase;

    /**
     * Media directory object (writable).
     *
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $mediaDirectory;
    
    /**
     * @var ProductMetadataInterface
     */
    protected $_productMetadata;


    /**
     * 
     * @param Download $download
     * @param Data $dataHelper
     * @param DirectoryList $directoryList
     * @param ProductMetadataInterface $productMetadata
     * @param \Magento\MediaStorage\Helper\File\Storage\Database $coreFileStorageDatabase
     * @param \Magento\Framework\Filesystem $filesystem
     */
    public function __construct(
        Download $download,
        Data $dataHelper,
        DirectoryList $directoryList,
        ProductMetadataInterface $productMetadata,
        \Magento\MediaStorage\Helper\File\Storage\Database $coreFileStorageDatabase,
        \Magento\Framework\Filesystem $filesystem
    )
    {
        $this->_download = $download;
        $this->_dataHelper = $dataHelper;
        $this->_directoryList = $directoryList;
        $this->_productMetadata = $productMetadata;
        $this->coreFileStorageDatabase = $coreFileStorageDatabase;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
        
    }
 
    public function aroundMoveFileFromTmp(ImageUpload $subject, $result, $imageName)
    {

        if (!$this->_dataHelper->isEnabled()) return $result;
            
        $baseTmpPath = $subject->getBaseTmpPath();
        $basePath = $subject->getBasePath();

        $baseImagePath = $subject->getFilePath($basePath, $imageName);
        $baseTmpImagePath = $subject->getFilePath($baseTmpPath, $imageName);
        $version = $this->_productMetadata->getVersion();

        try {
            $this->coreFileStorageDatabase->copyFile(
                    $baseTmpImagePath, $baseImagePath
            );
            
           if (version_compare($version, '2.3.4') < 0) {
                // Do nothing
            } else {
                $this->compressImage($imageName, $subject);
            }

            $this->mediaDirectory->renameFile(
                    $baseTmpImagePath, $baseImagePath
            );
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
            __('Something went wrong while saving the file(s).')
            );
        }
        
        if (version_compare($version, '2.3.4') < 0) {
            $this->compressImage($imageName, $subject);  
        }

        return $imageName;
    }

    /**
     * Compress image
     * 
     * @param string $imageName
     */
    public function compressImage($imageName, $subject)
    {

        $version = $this->_productMetadata->getVersion();

        /**
         * Get the list of allowed pages to compressed images when uploading from admin
         * @var array $listOfAllowedPages
         */
        $listOfAllowedPages = $this->_dataHelper->getCompressImageFolders();

        if (version_compare($version, '2.3.4') < 0) {

            /**
             * Get the the base path of the Image which uploaded from category
             * @var string $basePath
             */
            $basePath = $subject->getBasePath();

            /**
             * Get the base image path
             * @var string $basePath
             */
            $baseImagePath = $subject->getFilePath($basePath, $imageName);

            /**
             * Get the media directory path
             * @var String $mediaPath
             */
            $mediaPath = $this->_directoryList->getPath(DirectoryList::MEDIA);

            if (in_array(pages::PAGE_TYPE_CATEGORY, $listOfAllowedPages) &&
                    isset($mediaPath) &&
                    isset($baseImagePath)) {
                $this->_download->compressImage($mediaPath . DIRECTORY_SEPARATOR . $baseImagePath);
            }
        } else {
            /**
             * Get the the base path of the Image which uploaded from category
             * @var string $basePath
             */
            $basePath = $this->getBasePath();

            /**
             * Get the base image path
             * @var string $basePath
             */
            $baseImagePath = $this->getFilePath($basePath, $imageName);

            /**
             * Get the media directory path
             * @var String $mediaPath
             */
            //$mediaPath = $this->_directoryList->getPath(DirectoryList::MEDIA);
            $mediaPath = $this->_directoryList->getRoot();

            //check if category page is allowed to compress images
            if (in_array(pages::PAGE_TYPE_CATEGORY, $listOfAllowedPages) &&
                    isset($mediaPath) &&
                    isset($baseImagePath)) {
                $this->_download->compressImage($mediaPath . $baseImagePath);
            }
        }
    }

}
