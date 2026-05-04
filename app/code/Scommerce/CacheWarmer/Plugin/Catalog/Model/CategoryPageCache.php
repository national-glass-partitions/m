<?php
/**
 * Scommerce Cache Warmer Module all event hit plugin
 *
 * @category   Scommerce
 * @package    Scommerce_CacheWarmer
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */

namespace Scommerce\CacheWarmer\Plugin\Catalog\Model;

use Magento\Catalog\Controller\Adminhtml\Category\Save;
use Scommerce\CacheWarmer\Helper\Data;

/**
 * Class CategoryPageCache
 * 
 * @package Scommerce_CacheWarmer
 */
class CategoryPageCache
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
     * Generating the cache after save the category
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
        if (isset($data['entity_id'])) {
            $categoryId = $data['entity_id'];
            $this->_helper->deleteCWRecords('category', $categoryId);
            if (isset($categoryId) && $categoryId && $this->_helper->isEnabled() && $this->_helper->isRegenerateCache() && in_array('category', $includeFolders)) {
                $this->_helper->cacheRun('category', $categoryId);
            }
            return $result;
        } else {
            return $result;
        }
    }
}
