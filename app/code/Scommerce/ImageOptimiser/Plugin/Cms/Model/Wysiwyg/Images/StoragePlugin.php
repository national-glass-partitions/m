<?php

/**
 * Scommerce Mage - Plugin to intercept upload file function of CMS pages
 *
 * @category   Scommerce
 * @package    Scommerce_ImageOptimiser
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */

namespace Scommerce\ImageOptimiser\Plugin\Cms\Model\Wysiwyg\Images;

use Magento\Cms\Model\Wysiwyg\Images\Storage as WsyiwygStorage;
use Scommerce\ImageOptimiser\Model\Download;
use Scommerce\ImageOptimiser\Helper\Data;
use Scommerce\ImageOptimiser\Model\Config\Source\Pages;

/**
 * Class Storage
 * @package Scommerce\ImageOpimiser\Plugin
 */
class StoragePlugin extends WsyiwygStorage{

    /**
     * @var Download
     */
    protected $_download;
    
     /**
     * @var Data
     */
    protected $_dataHelper;

    /**
     * @param Download $download
     * @param Data $dataHelper
     */
    public function __construct(
        Download $download,
        Data $dataHelper       
    )
    {
        $this->_download = $download;
        $this->_dataHelper = $dataHelper;
    }

    /**
     * Overriding upload file function to compress image which are getting uploaded from CMS pages
     *
     * @param WsyiwygStorage $subject
     * @param array $result
     * @return array $result
     * @throws
     */
    public function afterUploadFile(WsyiwygStorage $subject, $result)
    {
        if (!$this->_dataHelper->isEnabled()) return $result;

        /**
         * Get the list of allowed pages to compressed images when uploading from admin
         * @var array $listOfAllowedPages
         */
        $listOfAllowedPages = $this->_dataHelper->getCompressImageFolders();

        //check if cms page is allowed to compress images
        if (isset($result['path']) && isset($result['file']) && in_array(Pages::PAGE_TYPE_CMS, $listOfAllowedPages) ){
            $this->_download->compressImage($result['path'].DIRECTORY_SEPARATOR.$result['file']);
        }
        return $result;
    }
}
