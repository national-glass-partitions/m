<?php
/**
 * Scommerce Cache Warmer module for product plugin
 *
 * @category   Scommerce
 * @package    Scommerce_CacheWarmer
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */

namespace Scommerce\CacheWarmer\Plugin\Catalog\Model;

use Magento\Catalog\Controller\Adminhtml\Product\Save;
use Scommerce\CacheWarmer\Helper\Data;

/**
 * Class ProductPageCache
 * 
 * @package Scommerce_CacheWarmer
 */
class ProductPageCache
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
        $data = $subject->getRequest()->getPostValue(); 
        $stockData = $data['product']['stock_data'];
        $includeFolders = $this->_helper->getIncludePages();
        if (isset($stockData['product_id'])) {
            $productId = $stockData['product_id'];
            $this->_helper->deleteCWRecords('product', $productId);	
            if ($productId && $this->_helper->isEnabled() &&	
                $this->_helper->isRegenerateCache() && in_array('product', $includeFolders)) {	
                $this->_helper->cacheRun('product', $productId);	
            }
        }
        return $result;	
    }
}
