<?php
/**
 *  Scommerce LazyLoading block class for render the html on view
 *
 * @category   Scommerce
 * @package    Scommerce_LazyLoading
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */
namespace Scommerce\LazyLoading\Block;

/**
 * Class Image
 * @package Scommerce_LazyLoading
 */
class Image extends \Magento\Framework\View\Element\Template
{
    
    /**
     * @var \Scommerce\LazyLoading\Helper\Data
     */
    public $helper;
     
    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Scommerce\LazyLoading\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Scommerce\LazyLoading\Helper\Data $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        parent::__construct($context, $data);
    }
    
    /**
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->helper->isEnabled()) {
            return '';
        }
        return parent::_toHtml();
    }
}
