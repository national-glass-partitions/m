<?php

/**
 * Scommerce Mage - Plugin to intercept upload file function for category page
 *
 * @category   Scommerce
 * @package    Scommerce_ImageOptimiser
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */

namespace Scommerce\ImageOptimiser\Plugin\Catalog\Model\Product\Image;

use Magento\Catalog\Controller\Adminhtml\Product\Save;
use Scommerce\ImageOptimiser\Model\Config\Source\Pages;
use Scommerce\ImageOptimiser\Model\Download;
use Scommerce\ImageOptimiser\Helper\Data;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Class Storage
 * @package Scommerce\ImageOpimiser\Plugin
 */
class ImagePlugin extends Save {

    /**
     * @var Download
     */
    protected $_download;
    
    /**
     * @var Data
     */
    protected $_dataHelper;

    /**
     * @var DirectoryList
     */
    protected $_directoryList;

    /**
     * @param Download $download
     * @param Data $dataHelper
     * @param DirectoryList $directoryList
     */
    public function __construct(
        Download $download,
        Data $dataHelper,
        DirectoryList $directoryList
    )
    {
        $this->_download  = $download;
        $this->_dataHelper = $dataHelper;
        $this->_directoryList = $directoryList;
    }

    /**
     * Overriding save file function to compress image which are getting uploaded from product page
     *
     * @param Save $subject
     * @param array $result
     * @return array $result
     * @throws
     */
    public function afterExecute(Save $subject, $result)
    {
        if (!$this->_dataHelper->isEnabled()) return $result;

        /**
         * Get the media directory path
         * @var String $mediaPath
         */
        $mediaPath = $this->_directoryList->getPath(DirectoryList::MEDIA).'/catalog/product';

        /**
         * get post value from product save
         * @var array $data
         */
        $data = $subject->getRequest()->getPostValue();

        /**
         * get the list of images from save product
         * @var array $images
         */
        if(isset($data['product']['media_gallery']) && $data['product']['media_gallery']) {
            $images = $data['product']['media_gallery']['images'];

            /**
             * Get the list of allowed pages to compressed images when uploading from admin
             * @var array $listOfAllowedPages
             */
            $listOfAllowedPages = $this->_dataHelper->getCompressImageFolders();

            foreach ($images as $image) {
                if ($image['value_id'] == null) {
                    $baseImagePath = str_replace('.tmp', '', $image['file']);

                    //check if product page is allowed to compress images
                    if (in_array(Pages::PAGE_TYPE_PRODUCT, $listOfAllowedPages) &&
                            isset($mediaPath) &&
                            isset($baseImagePath)) {
                        $this->_download->compressImage($mediaPath . $baseImagePath);
                    }
                }
            }
        }
        return $result;
    }
}
