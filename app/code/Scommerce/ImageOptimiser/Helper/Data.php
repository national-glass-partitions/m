<?php
/**
 *  Scommerce Image Optimiser helper class for common functions and retrieving configuration values
 *
 * @category   Scommerce
 * @package    Scommerce_ImageOptimiser
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */

namespace Scommerce\ImageOptimiser\Helper;

use Magento\Store\Model\ScopeInterface;
use Scommerce\ImageOptimiser\Logger\Logger;
use Magento\Framework\App\Helper\Context;
use Scommerce\Core\Helper\Data as CoreHelper;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\App\Filesystem\DirectoryList as MageFolderList;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;
use Scommerce\OptimiserBase\Helper\Data as OptimiserBaseData;

/**
 * Helper class data
 * @package Scommerce\ImageOptimiser\Helper
 */
class Data extends OptimiserBaseData
{
    const XML_PATH_EXTENSION_ENABLED = 'imageoptimiser/general/enabled';
    const XML_PATH_COMPRESS_IMAGE_FOLDERS = 'imageoptimiser/general/compress_images_while_uploading';
    const XML_PATH_COMPRESS_MEDIA_FOLDERS = 'imageoptimiser/general/include_folders_to_compress';
    const XML_PATH_ENABLED_LOG = 'imageoptimiser/general/debugging';
    const XML_PATH_COMPRESS_CACHE = 'imageoptimiser/general/compress_cached_product_images';
    const XML_PATH_NO_OF_IMAGE_PROCESSED = 'imageoptimiser/general/number_of_images_to_processed';
    const XML_PATH_MINIMUM_SIZE = 'imageoptimiser/general/minimum_size';
    const XML_PATH_API_PROVIDER = 'imageoptimiser/general/image_compression_provider';
    const XML_PATH_API_API_URL = 'imageoptimiser/general/provider_api_url';
    const XML_PATH_API_KEY = 'imageoptimiser/general/api_key';
    const XML_PATH_API_SECRET_KEY = 'imageoptimiser/general/api_secret_key';
    const XML_PATH_API_EXCLUDE_FOLDERS = 'imageoptimiser/general/exclude_folders';
    const XML_PATH_BACKUP_IMAGES = 'imageoptimiser/general/backup_images';
    const XML_PATH_DEBUGGING = 'imageoptimiser/general/debugging';

    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @param Context $context
     * @param Logger $logger
     * @param DirectoryList $directoryList
     * @param StoreManagerInterface $storeManager
     * @param CoreHelper $coreHelper
     */
    public function __construct(
        Context $context,
        Logger $logger,
        DirectoryList $directoryList,
        StoreManagerInterface $storeManager,
        CoreHelper $coreHelper
    )
    {
        parent::__construct($context, $coreHelper);
        $this->context = $context;
        $this->directoryList = $directoryList;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * Retrieve true if Image module is enabled
     * @param int $storeId
     * @return bool
     */
    public function isEnabled($storeId = null)
    {
        if (parent::isEnabled()) {
            return $this->isSetFlag(self::XML_PATH_EXTENSION_ENABLED,
                ScopeInterface::SCOPE_STORE, $storeId);
        }
        return false;
    }

    /**
     * Retrieve store config value
     * @param string $path
     * @param int $storeId
     * @return mixed
     */
    public function getConfig($path, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $path, ScopeInterface::SCOPE_STORE, $storeId
        );
    }

    /**
     * Return whether module log is enabled or not
     * @param int $storeId
     * @return boolean
     */
    public function isEnabledLog($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED_LOG, ScopeInterface::SCOPE_STORE, $storeId
        );
    }

    /**
     * Return how many images processed for compress
     * @param int $storeId
     * @return int
     */
    public function noOfImagesProcessed($storeId = null)
    {
        return $this->getValue(self::XML_PATH_NO_OF_IMAGE_PROCESSED, ScopeInterface::SCOPE_STORE, $storeId);
    }
    
    /**
     * Return minimum image size after image optimisation 
     * @param int $storeId
     * @return int
     */
    public function minImageSizeProcessed($storeId = null)
    {
        return $this->getValue(self::XML_PATH_MINIMUM_SIZE, ScopeInterface::SCOPE_STORE, $storeId);
    }
    
    /**
     * returns whether module is enabled or not
     * @param int $storeId
     * @return boolean
     */
    public function isCompressCacheEnabled($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_COMPRESS_CACHE, ScopeInterface::SCOPE_STORE, $storeId
        );
    }

    /**
     * returns image optimiser provider name
     * @param int $storeId
     * @return string
     */
    public function getProviderName($storeId = null)
    {
        return $this->getValue(self::XML_PATH_API_PROVIDER, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * returns image optimiser provider API URL
     * @param int $storeId
     * @return string
     */
    public function getProviderApiUrl($storeId = null)
    {
        return $this->getValue(self::XML_PATH_API_API_URL, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * returns provider api key
     * @param int $storeId
     * @return string
     */
    public function getProviderApiKey($storeId = null)
    {
        return $this->getValue(self::XML_PATH_API_KEY, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * returns provider api secret
     * @param int $storeId
     * @return string
     */
    public function getProviderApiSecret($storeId = null)
    {
        return $this->getValue(self::XML_PATH_API_SECRET_KEY, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * returns whether debugging is enabled or not
     * @param int $storeId
     * @return boolean
     */
    public function isBackupImagesEnabled($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_BACKUP_IMAGES, ScopeInterface::SCOPE_STORE, $storeId
        );
    }

    /**
     * returns whether debugging is enabled or not
     * @param int $storeId
     * @return boolean
     */
    public function isDebuggingEnabled($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_DEBUGGING, ScopeInterface::SCOPE_STORE, $storeId
        );
    }

    /**
     * returns Compress images directories
     * @param int $storeId
     * @return String
     */
    public function getCompressImageFolders($storeId = null)
    {
        $imageFolders = $this->scopeConfig->getValue(self::XML_PATH_COMPRESS_IMAGE_FOLDERS, ScopeInterface::SCOPE_STORE, $storeId);
        return $imageFolders !== null ? explode(',', $imageFolders) : [];
    }

    /**
     * returns Compress media directories which need to be compress
     * @param int $storeId
     * @return String
     */
    public function getCompressMediaFolders($storeId = null)
    {
        $mediaFolders = $this->scopeConfig->getValue(self::XML_PATH_COMPRESS_MEDIA_FOLDERS, ScopeInterface::SCOPE_STORE, $storeId);
        return $mediaFolders !== null ? explode(',', $mediaFolders) : [];
    }

    /**
     * Get exclude sub folders of the media
     * @param int $storeId
     * @return array
     */
    public function getExcludeFolders($storeId = null)
    {
        $excludeDirs = $this->getValue(self::XML_PATH_API_EXCLUDE_FOLDERS, ScopeInterface::SCOPE_STORE, $storeId);
        return $excludeDirs !== null ? explode(',', $excludeDirs) : [];
    }


    /**
     * Helper method for retrieve config value by path and scope
     *
     * @param string $path The path through the tree of configuration values, e.g., 'general/store_information/name'
     * @param string $scopeType The scope to use to determine config value, e.g., 'store' or 'default'
     * @param null|string $scopeCode
     * @return mixed
     */
    protected function getValue($path, $scopeType = ScopeInterface::SCOPE_STORE, $scopeCode = null)
    {
        return $this->scopeConfig->getValue($path, $scopeType, $scopeCode);
    }

    /**
     * Helper method for retrieve config flag by path and scope
     *
     * @param string $path The path through the tree of configuration values, e.g., 'general/store_information/name'
     * @param string $scopeType The scope to use to determine config value, e.g., 'store' or 'default'
     * @param null|string $scopeCode
     * @return bool
     */
    protected function isSetFlag($path, $scopeType = ScopeInterface::SCOPE_STORE, $scopeCode = null)
    {
        return $this->scopeConfig->isSetFlag($path, $scopeType, $scopeCode);
    }

    /**
     * @param string $message
     * @param int $storeId
     */
    public function addInfo($message, $storeId = null)
    {
        if ($this->isEnabledLog($storeId)) {
            $this->logger->info(__($message));
        }
    }

    /**
     * @param string $message
     * @param int $storeId
     */
    public function addCritical($message, $storeId = null)
    {
        if ($this->isEnabledLog($storeId)) {
            $this->logger->crit(__($message));
        }
    }

    /**
     * This function will return file URL based on the physical path given
     * for example /htdocs/magento2/pub/media/home/home-eco.jpg will return https://www.mysite.com/pub/media/home/home-eco.jpg
     * @param string $path
     * @throws
     * @return string
     */
    public function convertPathToURL($path)
    {
        $url = $path;
        if ($path!==null) {
            $baseUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB);
            //$baseUrl = 'http://demo2.scommerce-mage.co.uk/';
            $url = str_replace($this->directoryList->getRoot().'/', $baseUrl,
                $path);
            return $url;
        }
        return $url;
    }

    /**
     * This function will return physical path based on the URL given
     * for example https://www.mysite.com/pub/media/home/home-eco.jpg will return /htdocs/magento2/pub/media/home/home-eco.jpg
     * @param string $url
     * @throws
     * @return string
     */
    public function convertUrlToPath($url)
    {
        $path = $url;
        if ($url!==null) {
            $path = str_replace($this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB),
                    $this->directoryList->getRoot(),
                    $url);
            return $path;
        }
        return $path;
    }

    /**
     * Returns all the folders from the given directory
     *
     * @param string $dir
     * @param array $includedFolders
     * @return array $folders
     *
     * @throws \Exception
     */
    public function getDirectory($dir = MageFolderList::MEDIA, $includedFolders = array())
    {
        $dir = $this->directoryList->getPath($dir);

        $dirs = new \DirectoryIterator($dir);
        $folders = array();

        $excludeFolders = array('downloadable','import');
        foreach ($dirs as $fileInfo) {
            if ($fileInfo->isDir()
                && !$fileInfo->isDot()
                && !in_array($fileInfo->getFilename(), $excludeFolders)
                && ((empty($includedFolders)) || (in_array($fileInfo->getFilename(), $includedFolders)))
                && (substr($fileInfo->getFilename(),0,1)!=='.')
            ) {
                $folders[] =  $fileInfo->getFilename();
            }
        }
        return $folders;
    }
}
