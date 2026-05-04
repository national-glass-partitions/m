<?php
/**
 * Scommerce Mage - Return the list of Module which media images we need to compress while uploading
 *
 * @category   Scommerce
 * @package    Scommerce_ImageOptimiser
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */
namespace Scommerce\ImageOptimiser\Model\Config\Source;

/**
 * Class Pages
 * @package    Scommerce_ImageOptimiser
 */
class Pages implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Show Page Images Need to Compress
     */
    const PAGE_TYPE_NONE = 'none';
    const PAGE_TYPE_CMS = 'cms';
    const PAGE_TYPE_PRODUCT = 'product';
    const PAGE_TYPE_CATEGORY = 'category';

    /**
     * Return list of
     *
     * @return array
     */
    public function toOptionArray()
    {
        return  [
            ['value' => self::PAGE_TYPE_NONE, 'label' => __('Please select')],
            ['value' => self::PAGE_TYPE_CMS, 'label' => __('CMS')],
            ['value' => self::PAGE_TYPE_PRODUCT, 'label' => __('Product')],
            ['value' => self::PAGE_TYPE_CATEGORY, 'label' => __('Category')],
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $array = [];
        foreach ($this->toOptionArray() as $item) {
            $array[$item['value']] = $item['label'];
        }
        return $array;
    }
}

