<?php
/**
 * Scommerce_CacheWarmer file for include pages
 *
 * @category   Scommerce
 * @package    Scommerce_CacheWarmer
 * @author     Sommerce Mage <core@scommerce-mage.co.uk>
 */

namespace Scommerce\CacheWarmer\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class IncludePages
 * @package Scommerce_CacheWarmer
 */
class IncludePages implements ArrayInterface {

    /**
     * Return options array
     *
     * @return array
     */
    public function toOptionArray() {
        return $optioArray = array(
            array('value' => 'product', 'label' => __('Product Pages')),
            array('value' => 'category', 'label' => __('Category Pages')),
            array('value' => 'cms-page', 'label' => __('CMS Pages')));
    }       

}
