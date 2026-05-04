<?php
/**
 * Scommerce Cache Warmer Module cms page save hit plugin
 *
 * @category   Scommerce
 * @package    Scommerce_CacheWarmer
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */
namespace Scommerce\CacheWarmer\Plugin\Cms\Page;

use Magento\Cms\Controller\Adminhtml\Page\Save;
use Scommerce\CacheWarmer\Helper\Data;

/**
 * Class CmsPageCache
 * 
 * @package Scommerce_CacheWarmer
 */
class CmsPageCache
{
    /**
     * @var Helper
     */
    protected $_helper;

    /**
     * @param Data $dataHelper
     */
    public function __construct(
        Data $helper
    ) {
        $this->_helper = $helper;
    }
   
    /**
     * Generating the cache after save the product
     *
     * @param Save $subject
     * @param array $result
     * @return array $result
     * @throws
     */
    public function afterExecute(Save $subject, $result)
    {  
        /**
         * get post value from category save
         * @var array $data
         */
        $data = $subject->getRequest()->getPostValue();
        $includeFolders = $this->_helper->getIncludePages();
        $pageId = $data['page_id'];
        $this->_helper->deleteCWRecords('cms-page', $pageId);
        if (isset($pageId) && $pageId && $this->_helper->isEnabled() &&
            $this->_helper->isRegenerateCache() && in_array('cms-page', $includeFolders)) {
            $this->_helper->cacheRun('cms-page', $pageId);
        }
        return $result;
    }
}