<?php
/**
 * Scommerce LazyLoading  config file for exclude Pages
 *
 * @category   Scommerce
 * @package    Scommerce_LazyLoading
 * @author     Sommerce Mage <core@scommerce-mage.co.uk>
 */

namespace Scommerce\LazyLoading\Model\Config\Source;

/**
 * Class ExcludePages
 * @package Scommerce_LazyLoading
 */
class ExcludePages implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * Return options array
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [['value' => 0, 'label' => 'None'],
            ['value' => 1, 'label' => __('Home Page')],
            ['value' => 2, 'label' => __('Category Pages')],
            ['value' => 3, 'label' => __('Product Pages')],
            ['value' => 4, 'label' => __('CMS Pages')],
            ['value' => 5, 'label' => __('Search Pages')],
            ['value' => 6, 'label' => __('Cart Page')]];
    }
}
