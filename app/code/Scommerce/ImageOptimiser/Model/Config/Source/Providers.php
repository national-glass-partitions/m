<?php
/**
 * Scommerce Mage - Return the list of API providers which we will use for the compress the image
 *
 * @category   Scommerce
 * @package    Scommerce_ImageOptimiser
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */
namespace Scommerce\ImageOptimiser\Model\Config\Source;
use Magento\Framework\Option\ArrayInterface;

/**
 * Class Providers
 * @package    Scommerce_ImageOptimiser
 */
class Providers implements ArrayInterface
{
    /**
     * Show Page Images Need to Compress
     */
    const COMPRESSION_PROVIDER_TYPE_NONE = 'none';
    const COMPRESSION_PROVIDER_TYPE_SMUSH = 'resmush.it';
    const COMPRESSION_PROVIDER_TYPE_IMAGEOPTIM = 'imageoptim';
    const COMPRESSION_PROVIDER_TYPE_KRAKENIP = 'kraken.io';

    /**
     * Options string
     *
     * @return array
     */
    public function toOptionArray()
    {
        return  [
            ['value' => self::COMPRESSION_PROVIDER_TYPE_NONE, 'label' => __('Please select')],
            ['value' => self::COMPRESSION_PROVIDER_TYPE_SMUSH, 'label' => __('Resmush')],
            ['value' => self::COMPRESSION_PROVIDER_TYPE_IMAGEOPTIM, 'label' => __('Imageoptim')],
            ['value' => self::COMPRESSION_PROVIDER_TYPE_KRAKENIP, 'label' => __('Kraken')],
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



