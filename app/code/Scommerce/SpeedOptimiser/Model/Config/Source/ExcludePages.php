<?php
/**
 * Scommerce_SpeedOptimiser  config file for exclude Pages
 *
 * @category   Scommerce
 * @package    Scommerce_SpeedOptimiser
 * @author     Sommerce Mage <core@scommerce-mage.co.uk>
 */

namespace Scommerce\SpeedOptimiser\Model\Config\Source;

/**
 * Class ExcludePages
 * @package Scommerce_SpeedOptimiser
 */
class ExcludePages implements \Magento\Framework\Option\ArrayInterface {

    /**
     * Return options array
     *
     * @return array
     */
    public function toOptionArray() {
        return $optioArray = array(array('value' => 0, 'label' => 'None'),
            array('value' => 1, 'label' => __('Home Page')),
            array('value' => 2, 'label' => __('Category Pages')),
            array('value' => 3, 'label' => __('Product Pages')),
            array('value' => 4, 'label' => __('CMS Pages')),
            array('value' => 5, 'label' => __('Search Pages')),
            array('value' => 6, 'label' => __('Cart Page')));
    }

}
