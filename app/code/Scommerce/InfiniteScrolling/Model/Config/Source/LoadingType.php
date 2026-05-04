<?php
/**
 * Scommerce InfiniteScrolling model class for custom source
 *
 * @category   Scommerce
 * @package    Scommerce_InfiniteScrolling
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */

namespace Scommerce\InfiniteScrolling\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class LoadingType
 * @package Scommerce_InfiniteScrolling
 */
class LoadingType implements ArrayInterface {

    /**
     * @return array
     */
    public function toOptionArray() {
        return [
            ['value' => 0, 'label' => __('Load Automatically')],
            ['value' => 1, 'label' => __('Load With Button')],
        ];
    }
}
