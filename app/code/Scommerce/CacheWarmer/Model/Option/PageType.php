<?php
/**
 * Scommerce Status Class
 * 
 * @category   Scommerce
 * @package    Scommerce_CacheWarmer
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */
namespace Scommerce\CacheWarmer\Model\Option;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Product type model
 *
 * @api
 * @since 100.0.2
 */
class PageType implements OptionSourceInterface
{
    
    /**
     * Get Grid row status type labels array.
     * @return array
     */
    public function getOptionArray()
    {
        return [
            'category' => __('Category'),
            'cms-page' => __('Cms Page'),
            'product' => __('Product'),
        ];
    }

    /**
     * Get Grid row status labels array with empty value for option element.
     *
     * @return array
     */
    public function getAllOptions()
    {
        $res = $this->getOptions();
        array_unshift($res, ['value' => '', 'label' => '']);
        return $res;
    }

    /**
     * Get Grid row type array for option element.
     * @return array
     */
    public function getOptions()
    {
        $res = [];
        foreach ($this->getOptionArray() as $index => $value) {
            $res[] = ['value' => $index, 'label' => $value];
        }
        return $res;
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return $this->getOptions();
    }
}
