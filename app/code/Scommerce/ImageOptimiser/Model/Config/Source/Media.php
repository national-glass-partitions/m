<?php
/**
 * Scommerce Mage - Return the list of folders under media folder
 *
 * @category   Scommerce
 * @package    Scommerce_ImageOptimiser
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */
namespace Scommerce\ImageOptimiser\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Scommerce\ImageOptimiser\Helper\Data;

/**
 * Class Media
 * @package    Scommerce_ImageOptimiser
 */
class Media implements ArrayInterface
{
    /**
     * @var Data
     */
    protected $_data;

    /**
     * @param Data $data
     */
    public function __construct(
        Data $data
    ) {
       $this->_data = $data;
    }

    /**
     * Get options in "key value" format
     * @throws
     * @return array
     */
    public function toOptionArray()
    {
        $folders = $this->_data->getDirectory();
        $options = [];
        $options[] = [
            'value' => '',
            'label' => 'Please Select'
        ];
        foreach ($folders as $folder) {
            $options[] = [
                'value' => $folder,
                'label' => $folder
            ];
        }
        return $options;
    }
}





