<?php
/**
 * Scommerce SpeedOptimiser  plugin file to change the file size
 *
 * @category   Scommerce
 * @package    Scommerce_SpeedOptimiser
 * @author     Sommerce Mage <core@scommerce-mage.co.uk>
 * 
 */

namespace Scommerce\SpeedOptimiser\Plugin\Deploy\Config;

use Magento\Deploy\Config\BundleConfig;
use Scommerce\SpeedOptimiser\Helper\Data;


/**
 * Class BundleConfig
 * @package Scommerce_SpeedOptimiser
 */
class BundleConfigPlugin
{
    
    /**
     * @var Data
     */
    protected $helper;
    

    /**
     * __construct
     * 
     * @param Data $helper
     */
    public function __construct(Data $helper)
    {
        $this->helper = $helper;
    }
    

    /**
     * Set the new Max size for bundle files
     * 
     * @param object $subject
     * @param int $result
     * @param string $area
     * @param string $theme
     * @return int
     */
    public function afterGetBundleFileMaxSize(BundleConfig $subject, $result, $area, $theme) {
        if ($filesize = $this->helper->checkBundlingFilesize()) {
            return $filesize;
        } else {
            return $result;
        }
    }

}
