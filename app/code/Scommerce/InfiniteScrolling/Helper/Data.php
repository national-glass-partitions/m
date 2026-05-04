<?php
/**
 *  Scommerce InfiniteScrolling helper class for common functions and retrieving configuration values
 *
 * @category   Scommerce
 * @package    Scommerce_InfiniteScrolling
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */

namespace Scommerce\InfiniteScrolling\Helper;

use Scommerce\OptimiserBase\Helper\Data as OptimiserBaseData;
use Scommerce\Core\Helper\Data as CoreHelperData;
use Magento\Framework\App\Helper\Context;

/**
 * Class Data
 * @package Scommerce_InfiniteScrolling
 */
class Data extends OptimiserBaseData
{
    /**
     * @const config path
     */    
    const INFINITESCROLLING_ENABLED                 = 'scommerce_infinitescrolling/general/enabled';
    const INFINITESCROLLING_LOADING_TYPE            = 'scommerce_infinitescrolling/general/loading_type';
    const INFINITESCROLLING_BUTTON_LABEL            = 'scommerce_infinitescrolling/general/button_label';
    const INFINITESCROLLING_BUTTON_LABEL_FONT_COLOR = 'scommerce_infinitescrolling/general/button_label_font_color';
    const INFINITESCROLLING_BUTTON_LABEL_BACKGROUND = 'scommerce_infinitescrolling/general/button_label_background_color';
    const INFINITESCROLLING_BUTTON_LABEL_SIZE       = 'scommerce_infinitescrolling/general/button_label_size';
    const INFINITESCROLLING_DISPLAY_PAGE_NUMBER     = 'scommerce_infinitescrolling/general/display_page_number';
    const INFINITESCROLLING_GRID_CLASS              = 'scommerce_infinitescrolling/general/grid_class';
    const INFINITESCROLLING_LIST_CLASS              = 'scommerce_infinitescrolling/general/list_class';
    const CATALOG_LIST_MODE                         = 'catalog/frontend/list_mode';
    
    /**
     * __construct
     * 
     * @param Context $context
     * @param CoreHelperData $coreHelper
     */
    public function __construct(
        Context $context,
        CoreHelperData $coreHelper
    ) {
        parent::__construct($context, $coreHelper);
    }    
    
    /**
     * Check, if module active or not
     *
     * @return bool
     */
    public function isEnabled() { 
        if (parent::isEnabled()) {
            return $this->isSetFlag(self::INFINITESCROLLING_ENABLED);
        }
    }

    /**
     * Getting loading type
     *
     * @return bool
     */
    public function loadingType() {
        return $this->getValue(self::INFINITESCROLLING_LOADING_TYPE);
    }

    /**
     * Get button label
     *
     * @return string
     */
    public function buttonLabel() {
        return $this->getValue(self::INFINITESCROLLING_BUTTON_LABEL);
    }

    /**
     * Get the button label font color
     *
     * @return bool
     */
    public function buttonLabelFontColor() {
        return $this->getValue(self::INFINITESCROLLING_BUTTON_LABEL_FONT_COLOR);
    }

    /**
     * Get the button label background color
     *
     * @return string
     */
    public function buttonLabelBackground() {
        return $this->getValue(self::INFINITESCROLLING_BUTTON_LABEL_BACKGROUND);
    }

    /**
     * Get the button label size
     *
     * @return string
     */
    public function buttonLabelSize() {
        return $this->getValue(self::INFINITESCROLLING_BUTTON_LABEL_SIZE);
    }

    /**
     * Check, if display the info bar
     *
     * @return bool
     */
    public function displayPageNumber() {
        return $this->getValue(self::INFINITESCROLLING_DISPLAY_PAGE_NUMBER);
    }

    /**
     * Get the dom class for grid mode
     *
     * @return string
     */
    public function gridClass() {
        return $this->getValue(self::INFINITESCROLLING_GRID_CLASS);
    }

    /**
     * Get the dom class for list mode
     *
     * @return string
     */
    public function listClass() {
        return $this->getValue(self::INFINITESCROLLING_LIST_CLASS);
    }

    /**
     * Get the mode type
     * 
     * @return string
     */
    public function getCatalogListMode() {
        return $this->getValue(self::CATALOG_LIST_MODE);
    }
    
    /**
     * Is display page number in footer
     * 
     * @return boolean
     */
    public function isDisplayPageNumber() {
        if($this->displayPageNumber() && $this->isEnabled()){
            return true;
        }
    }

}
