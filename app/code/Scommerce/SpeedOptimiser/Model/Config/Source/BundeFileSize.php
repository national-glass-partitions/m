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
 * Class BundeFileSize
 * @package Scommerce_SpeedOptimiser
 */
class BundeFileSize implements \Magento\Framework\Option\ArrayInterface {

    /**
     * Return options array
     *
     * @return array
     */
    public function toOptionArray() {
        return $optioArray = array(array('value' => 100, 'label' => '100KB'),
            array('value' => 200, 'label' => __('200KB')),
            array('value' => 300, 'label' => __('300KB (Recommended)')),
            array('value' => 400, 'label' => __('400KB')),
            array('value' => 500, 'label' => __('500KB')),
            array('value' => 600, 'label' => __('600KB')),
            array('value' => 700, 'label' => __('700KB')),
            array('value' => 800, 'label' => __('800KB')),
            array('value' => 900, 'label' => __('900KB')),
            array('value' => 1000, 'label' => __('1000KB (1MB)')));
        
    }

}
