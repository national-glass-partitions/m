<?php
/**
 * Scommerce InfiniteScrolling block class for color selection in admin config
 *
 * @category   Scommerce
 * @package    Scommerce_InfiniteScrolling
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */
namespace Scommerce\InfiniteScrolling\Block;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class LoadingType
 * @package Scommerce_InfiniteScrolling
 */
class Color extends Field {
       
    /**
     * __construct
     * 
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context, 
        array $data = []
    ) {
        parent::__construct($context, $data);
    }
    
    /**
     * Retrieve element HTML markup
     * 
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element) {
        $html = $element->getElementHtml();

        $html .= '<script type="text/javascript">
                var el = document.getElementById("' . $element->getHtmlId() . '");
                el.className = el.className + " jscolor{hash:true}";
            </script>';

        return $html;
    }

}
