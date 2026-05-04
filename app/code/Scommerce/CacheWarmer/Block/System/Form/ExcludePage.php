<?php
/**
* Scommerce Cache Warmer block class for render the html in config
*
* @category Scommerce
* @package Scommerce_CacheWarmer
* @author Scommerce Mage <core@scommerce-mage.com>
*/
namespace Scommerce\CacheWarmer\Block\System\Form;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\Factory;
/**
* Class ExcludePage
* @package Scommerce_CacheWarmer
*/
class ExcludePage extends AbstractFieldArray
{
    /**
    * @var elementFactory
    */
    protected $_elementFactory;

    /**
    * @param $context
    * @param $elementFactory
    * @param array $data
    */
    public function __construct(
        Context $context,
        Factory $elementFactory,
        array $data = []
    )
    {
        $this->_elementFactory = $elementFactory;
        parent::__construct($context, $data);
    }

    /**
    * _construct
    */
    protected function _construct() 
    {
        $this->addColumn('exclude_page', ['label' => __('Exclude Pages')]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Exclude Pages');
        parent::_construct();
    }

}