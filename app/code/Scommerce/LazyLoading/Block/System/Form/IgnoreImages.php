<?php
/**
 *  Scommerce LazyLoading block class for render the html in config
 *
 * @category   Scommerce
 * @package    Scommerce_LazyLoading
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */
namespace Scommerce\LazyLoading\Block\System\Form;

/**
 *  Class IgnoreImages
 *  @package Scommerce_LazyLoading
 */
class IgnoreImages extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{
    /**
     * @var \Magento\Framework\Data\Form\Element\Factory
     */
    protected $_elementFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Data\Form\Element\Factory $elementFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Data\Form\Element\Factory $elementFactory,
        array $data = []
    ) {
        $this->_elementFactory  = $elementFactory;
        parent::__construct($context, $data);
    }
    
    /**
     * _construct
     */
    protected function _construct()
    {
        $this->addColumn('lazyimage', ['label' => __('Matched Expression')]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Match');
        parent::_construct();
    }
}
