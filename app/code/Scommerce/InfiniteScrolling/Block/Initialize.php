<?php
/**
 * Scommerce InfiniteScrolling block class to perform the scrolling action
 *
 * @category   Scommerce
 * @package    Scommerce_InfiniteScrolling
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */

namespace Scommerce\InfiniteScrolling\Block;

use \Magento\Framework\View\Element\Template;
use \Magento\Framework\View\Element\Template\Context;
use \Scommerce\InfiniteScrolling\Helper\Data;

/**
 * Class Initialize
 * @package Scommerce_InfiniteScrolling
 */
class Initialize extends Template
{      
    /**
     * @var Data
     */
    public $helper; 
    
    /**
     * __construct
     * 
     * @param Context $context
     * @param Data $helper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $helper,
        array $data = []
        ) {
        parent::__construct($context, $data);  
        $this->helper    = $helper; 

    }
    
    /**
     * Get product listing mode
     * 
     * @return string
     */
    private function getProductListMode() {
        if ($currentMode = $this->getRequest()->getParam('product_list_mode')) {
            switch ($currentMode) {
                case 'grid':
                    $productListMode = 'grid';
                    break;
                case 'list':
                    $productListMode = 'list';
                    break;
            }
        } else {
            $defaultMode = $this->helper->getCatalogListMode();
            switch ($defaultMode) {
                case 'grid-list':
                    $productListMode = 'grid';
                    break;
                case 'list-grid':
                    $productListMode = 'list';
                    break;
                case 'list':
                    $productListMode = 'list';
                    break;
                case 'grid':
                    $productListMode = 'grid';
                    break;
            }
        }
        return $productListMode;
    }
    
    /**
     * Get mode class from configuration
     * 
     * @return string
     */
    public function getModeClass() {
        $mode = $this->getProductListMode();
        switch ($mode) {
            case 'grid':
                if ($grid = $this->helper->gridClass()) {
                    $productMode = $grid;
                } else {
                    $productMode = '.products-grid';
                }
                break;
            case 'list':
                if ($list = $this->helper->listClass()) {
                    $productMode = $list;
                } else {
                    $productMode = '.products-list';
                }
                break;
        }

        return $productMode;
    }    
}
