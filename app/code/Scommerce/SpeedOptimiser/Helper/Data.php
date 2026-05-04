<?php
/**
 * Scommerce Speed Optimiser helper class for common functions and retrieving configuration values
 *
 * @category   Scommerce
 * @package    Scommerce_SpeedOptimiser
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */

namespace Scommerce\SpeedOptimiser\Helper;


use Scommerce\Core\Helper\Data as CoreHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Model\Exception;
use Magento\Store\Model\ScopeInterface;
use Scommerce\OptimiserBase\Helper\Data as OptimiserBaseData;
use Magento\Framework\App\Request\Http as Request;

class Data extends OptimiserBaseData
{
    /**
     * variable to check if extension is enable or not
     *
     * @var string
     */
    const ENABLED = 'speedoptimiser/general/enabled';

    /**
     * variable to check if html settings is enable or not
     *
     * @var string
     */
    const HTML_SETTING = 'speedoptimiser/settings/html/html_setting';
    
    
    /**
     * variable to check if defer iframe enable or not
     *
     * @var string
     */
    const HTML_DEFER_IFRAME = 'speedoptimiser/settings/html/defer_iframe';
    

    /**
     * variable to check if Merge Java script settings is enable or not
     *
     * @var string
     */
    const MERGE_JAVASCRIPT = 'speedoptimiser/settings/javascript/merge_javascript_files';


    /**
     * variable to check if Java script Bundling settings is enable or not
     *
     * @var string
     */
    const JAVASCRIPT_BUNDLING = 'speedoptimiser/settings/javascript/javascript_bundling';
    
    /**
     * variable to check the bundle file size
     *
     * @var string
     */
    const JAVASCRIPT_BUNDLING_FILESIZE = 'speedoptimiser/settings/javascript/javascript_bundling_filesize';


    /**
     * variable to check if Java script file minification settings is enable or not
     *
     * @var string
     */
    const JAVASCRIPT_FILE_MINIFICATION = 'speedoptimiser/settings/javascript/javascript_files_minification';

    /**
     * variable to check if Java script move to bottom settings is enable or not
     *
     * @var string
     */
    const JAVASCRIPT_PAGE_BOTTOM = 'speedoptimiser/settings/javascript/move_javascript_to_page_bottom';
    
    /**
     * variable to check if any pages selected to exclude pages to move page bottom
     *
     * @var string
     */
    const JAVASCRIPT__EXCLUDE_PAGES_TO_PAGE_BOTTOM = 'speedoptimiser/settings/javascript/exclude_pages_to_move_js_bottom';

    
    /**
     * variable to check if merge css file settings is enable or not
     *
     * @var string
     */
    const MERGE_CSS = 'speedoptimiser/settings/css/merge_css_files';

    /**
     * variable to check if minification css file settings is enable or not
     *
     * @var string
     */
    const CSS_MINIFICATION = 'speedoptimiser/settings/css/css_files_minification';

    /**
     * variable to check if minification css file settings is enable or not
     *
     * @var string
     */
    const DEFER_FONTS_SETTING = 'speedoptimiser/settings/css/defer_fonts_loading';

    /**
     * variable to check if database flat table is enable or not
     *
     * @var string
     */
    const FLAT_CATALOG_CATEGORY = 'speedoptimiser/settings/flat_tables/use_flat_catalog_category';

    /**
     * variable to check if flat catalog product settings is enable or not
     *
     * @var string
     */
    const FLAT_CATALOG_PRODUCT = 'speedoptimiser/settings/flat_tables/use_flat_catalog_product';


    /**
     * @var CoreHelper
     */
    protected $_coreHelper;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;
    
    /**
     * @var Request
     */
    protected $request;

    /**
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param CoreHelper $coreHelper
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        CoreHelper $coreHelper,
        Request $request
    )
    {
        parent::__construct($context, $coreHelper);
        $this->_scopeConfig = $scopeConfig;
        $this->_coreHelper = $coreHelper;
        $this->request = $request;
    }
    /**
     * returns whether module is enabled or not
     * @param int $storeId
     * @return boolean
     */
    public function isEnabled($storeId = null) {
        if (parent::isEnabled()) {
            return $this->isSetFlag(self::ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
        }
    }

    /**
     * returns whether HTML setting value
     * @param int $storeId
     * @return boolean
     */
    public function getHtmlCompress($storeId = null)
    {
         return $this->getValue(self::HTML_SETTING, ScopeInterface::SCOPE_STORE, $storeId);
    }
    /**
     * returns whether Merge Java script value
     * @param int $storeId
     * @return boolean
     */
    public function getMergeJavascriptValue($storeId = null)
    {
        return $this->getValue(self::MERGE_JAVASCRIPT, ScopeInterface::SCOPE_STORE, $storeId);
    }
    /**
     * returns whether Merge Java script bundling enabled or not
     * @param int $storeId
     * @return boolean
     */
    public function getJavascriptBundlingValue($storeId = null)
    {
        return $this->getValue(self::JAVASCRIPT_BUNDLING, ScopeInterface::SCOPE_STORE, $storeId);
    }
    /**
     * returns the value Java script files minification
     * @param int $storeId
     * @return int
     */
    public function getJavascriptFileMinificationValue($storeId = null)
    {
        return $this->getValue(self::JAVASCRIPT_FILE_MINIFICATION, ScopeInterface::SCOPE_STORE, $storeId);
    }
    /**
     * Get value of the default merge css value
     * @param int $storeId
     * @return int
     * */
    public function getMergeCssValue($storeId = null)
    {
        return $this->getValue(self::MERGE_CSS, ScopeInterface::SCOPE_STORE, $storeId);
    }
    /**
     * returns whether Css Minification value
     * @param int $storeId
     * @return boolean
     */
    public function getCssMinificationValue($storeId = null)
    {
        return $this->getValue(self::CSS_MINIFICATION, ScopeInterface::SCOPE_STORE, $storeId);
    }
    /**
     * returns whether Differ Font Setting enabled or not
     * @param int $storeId
     * @return boolean
     */
    public function isDeferFontsSettingEnabled($storeId = null) {
        if($this->getMergeCssValue()) {
            return $this->getValue(self::DEFER_FONTS_SETTING, ScopeInterface::SCOPE_STORE, $storeId);
        }
    }

    /**
     * returns whether Flat Catalog Category value
     * @param int $storeId
     * @return boolean
     */
    public function getFlatCatalogCategoryValue($storeId = null)
    {
        return $this->getValue(self::FLAT_CATALOG_CATEGORY, ScopeInterface::SCOPE_STORE, $storeId);
    }
    /**
     * returns whether Flat Catalog Category product value
     * @param int $storeId
     * @return boolean
     */
    public function getFlatCatalogProductValue($storeId = null)
    {
        return $this->getValue(self::FLAT_CATALOG_PRODUCT, ScopeInterface::SCOPE_STORE, $storeId);
    }
    
    /**
     * Checking, if javaScript move to bottom
     * @param int $storeId
     * @return bolean
     */
    public function getJavaScriptMoveToBottom($storeId = null) { 
         return $this->getValue(self::JAVASCRIPT_PAGE_BOTTOM, ScopeInterface::SCOPE_STORE, $storeId);
    }
    
    /**
     * Get exclude pages to move javaScript to move page bottom
     * 
     * @return array|null
     */
    public function excludePages($storeId) {
        return $this->getValue(self::JAVASCRIPT__EXCLUDE_PAGES_TO_PAGE_BOTTOM, ScopeInterface::SCOPE_STORE, $storeId);
    }
    
    
    /**
     * Checking, if javaScript move to bottom
     * @param int $storeId
     * @return bolean
     */
    public function isJavaScriptMoveToBottom($storeId = null) {         
                        
        if(!$this->getJavaScriptMoveToBottom($storeId)) {
            return false;
        }
        
        $action = $this->request->getFullActionName();
        $excludePages =  $this->excludePages($storeId);
        
        if(empty($excludePages)) {
            return true;
        } else {
            $pageArray  =  $this->getExcludePageArray($excludePages);
            return $this->checkExcludePages($action, $pageArray);
        } 
        
    }

    /**
     * Checking the exclude to move javaScript to move page bottom
     * 
     * @param string $action
     * @return boolean
     */
    protected function checkExcludePages($action, $pageArray) {
        
        $isExcludePage = false;
        switch ($action) {
            case "cms_index_index":
                $isExcludePage = !in_array(1, $pageArray);
                break;
            case "catalog_category_view":
                $isExcludePage = !in_array(2, $pageArray);
                break;
            case "catalog_product_view":
                $isExcludePage = !in_array(3, $pageArray);
                break;
            case "cms_page_view":
                $isExcludePage = !in_array(4, $pageArray);
                break;
            case "catalogsearch_result_index":
                $isExcludePage = !in_array(5, $pageArray);
                break;
            case "checkout_cart_index":
                $isExcludePage = !in_array(6, $pageArray);
                break;
        }
       return $isExcludePage;
    }
    
    /**
     * Get exclude pages array
     * 
     * @param type $excludePages
     * @return type
     */
    protected function getExcludePageArray($excludePages) {
        return explode(",", $excludePages);
    }
    
    
    /**
     * get the bundle file size
     * 
     * @return boolean
     */
    public function checkBundlingFilesize($storeId = null) {

        if (!$this->isEnabled($storeId)) {
            return false;
        }
        
        if($this->getMergeJavascriptValue($storeId)) {
            return false;
        }

        if (!$this->getJavascriptBundlingValue($storeId)) {
            return false;
        }

        return $this->getValue(self::JAVASCRIPT_BUNDLING_FILESIZE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Checking, if defer iframe
     * 
     * @return boolean
     */
    public function getDeferIframe($storeId = null) {
            return $this->getValue(self::HTML_DEFER_IFRAME, ScopeInterface::SCOPE_STORE, $storeId);
    }

}
