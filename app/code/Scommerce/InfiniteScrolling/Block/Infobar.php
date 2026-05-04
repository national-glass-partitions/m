<?php
/**
 * Scommerce InfiniteScrolling block class for info bar
 *
 * @category   Scommerce
 * @package    Scommerce_InfiniteScrolling
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */

namespace Scommerce\InfiniteScrolling\Block;

use \Magento\Framework\View\Element\Template;
/**
 * Class Infobar
 * @package Scommerce_InfiniteScrolling
 */
class Infobar extends Template
{
    
    /**
     * Get action name
     * 
     * @return string
     */
    public function getActionName() {
        return $this->_request->getFullActionName();
    }

    /**
     * Check, if scrolling enable for category, search and filter page
     * 
     * @param string $action
     * @return boolean
     */
    public function checkPage() {
        $action = $this->getActionName();
        switch ($action) {
            case "catalog_category_view":
                return true;
                break;
            case "catalog_category_view_type_default":
                return true;
                break;
            case "catalogsearch_result_index":
                return true;
                break;
        }   
    }

}
