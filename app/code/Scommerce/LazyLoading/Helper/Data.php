<?php
/**
 *  Scommerce LazyLoading helper class for common functions and retrieving configuration values
 *
 * @category   Scommerce
 * @package    Scommerce_LazyLoading
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */

namespace Scommerce\LazyLoading\Helper;

/**
 * Class Data
 * @package Scommerce_LazyLoading
 */
class Data extends \Scommerce\OptimiserBase\Helper\Data
{
    /**
     * @const config path
     */
    const LAZYLOADING_ENABLED                 = 'scommerce_lazyloading/general/enabled';
    const LAZYLOADING_PRELOAD_IMAGE           = 'scommerce_lazyloading/general/preload_image';
    const LAZYLOADING_SKIP_IMAGE              = 'scommerce_lazyloading/general/skip_image';
    const LAZYLOADING_SKIP_IMAGES_TAG         = 'scommerce_lazyloading/general/skip_images_tag';
    const LAZYLOADING_EXCLUDE_PAGES           = 'scommerce_lazyloading/general/exclude_pages';
    const LAZYLOADING_LOADING_IMAGE           = 'scommerce_lazyloading/general/loading';
    
    /**
     * @var $ignored
     */
    public static $ignored = 0;

    /**
     * @var $_skipImageTags
     */
    protected $_skipImageTags = null;

    /**
     * @var $_isExcludePage
     */
    protected $_isExcludePage = null;

    /**
     * @var $_skipImageCount
     */
    protected $_skipImageCount = null;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    public $serialize;
    
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    
    /**
     * @var \Magento\Framework\App\Request\Http
     */
    public $request;
    
    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Scommerce\Core\Helper\Data $coreHelper
     * @param \Magento\Framework\Serialize\Serializer\Json $serialize
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Scommerce\Core\Helper\Data $coreHelper,
        \Magento\Framework\Serialize\Serializer\Json $serialize,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Request\Http $request
    ) {
        $this->serialize = $serialize;
        $this->_storeManager=$storeManager;
        $this->request = $request;
        parent::__construct($context, $coreHelper);
    }
    
    /**
     * Check, if module active or not
     *
     * @return bool
     */
    public function isEnabled()
    {
        if (parent::isEnabled()) {
            return $this->isSetFlag(self::LAZYLOADING_ENABLED);
        }
    }
    
    /**
     * Check, if image setting is pre load
     *
     * @return bool
     */
    public function isPreLoadImage()
    {
        return $this->getValue(self::LAZYLOADING_PRELOAD_IMAGE);
    }
    
    /**
     * Get placeholder image
     *
     * @return string
     */
    public function getLazyImage()
    {
        $img = $this->getValue(self::LAZYLOADING_LOADING_IMAGE);

        if (!$img || $img == '') {
            return $this->getLazyImg();
        }
        
        return $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'lazyimage' . DIRECTORY_SEPARATOR . $img;
    }
    
    /**
     * Get the number count to exclude the images from Lazy Load
     *
     * @return int
     */
    public function skipImageCount()
    {
        return $this->getValue(self::LAZYLOADING_SKIP_IMAGE);
    }
    
    /**
     * Get image tags that will be excluded from the  Lazy Load
     *
     * @return array
     */
    public function skipImagesTag()
    {
        $skipImageTag =  $this->getValue(self::LAZYLOADING_SKIP_IMAGES_TAG);
        if ($skipImageTag) {
            return $this->serialize->unserialize($skipImageTag);
        }
    }
    
    /**
     * Get exclude pages from Lazy Load
     *
     * @return array|null
     */
    public function excludePages()
    {
        return $this->getValue(self::LAZYLOADING_EXCLUDE_PAGES);
    }
    
    /**
     * Get default placeholder
     *
     * @return string
     */
    protected function getLazyImg()
    {
            return 'data:image/gif;base64,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';
    }
    
    /**
     * Checking the exclude pages for lazy loading
     * @param string $action
     * @return boolean
     */
    public function checkExcludePages()
    {
        if ($this->_isExcludePage !== null) {
            return $this->_isExcludePage;
        }

        if (!$this->isEnabled()) {
            $this->_isExcludePage = false;
            return false;
        }

        $action = $this->request->getFullActionName();
        $excludePages = $this->excludePages();

        if (empty($excludePages)) {
            return true;
        } else {
            $pageArray = $this->getExcludePageArray($excludePages);
        }

        switch ($action) {
            case "cms_index_index":
                $this->_isExcludePage = !in_array(1, $pageArray);
                break;
            case "catalog_category_view":
                $this->_isExcludePage = !in_array(2, $pageArray);
                break;
            case "catalog_product_view":
                $this->_isExcludePage = !in_array(3, $pageArray);
                break;
            case "cms_page_view":
                $this->_isExcludePage = !in_array(4, $pageArray);
                break;
            case "catalogsearch_result_index":
                $this->_isExcludePage = !in_array(5, $pageArray);
                break;
            case "checkout_cart_index":
                $this->_isExcludePage = !in_array(6, $pageArray);
                break;
        }
        return $this->_isExcludePage;
    }

    /**
     * Get exclude pages array
     *
     * @param type $excludePages
     * @return type
     */
    private function getExcludePageArray($excludePages)
    {
        return explode(",", $excludePages);
    }
    
    /**
     * Applying the lazy loading
     *
     * @return boolean
     */
    public function applyLazyLoad()
    {
        if ($this->_skipImageCount == null) {
            $this->_skipImageCount = $this->skipImageCount();
        }
        
        if ($skipImage = $this->_skipImageCount) {
            if (self::$ignored < $skipImage * 1) {
                self::$ignored++;
                return false;
            }
        }
        return true;
    }

    /**
     * Check image tag
     *
     * @param string $result
     * @return boolean
     */
    public function checkImagesTag($result)
    {
        if ($this->_skipImageTags == null) {
            $this->_skipImageTags = $this->skipImagesTag();
        }

        $skipImages = [];
        foreach ($this->_skipImageTags as $skipImageTag) {
            if (!isset($skipImageTag['lazyimage'])) {
                continue;
            }
            array_push($skipImages, $skipImageTag['lazyimage']);
        }

        if (count($skipImages)) {
            foreach ($skipImages as $skipImage) {
                if ($skipImage && strpos($result, $skipImage)!== false) {
                    return true;
                }
            }
        }
    }
}
