<?php
/**
 * Image Optimizer Logger Handler class for creating the log file
 *
 * @category   Scommerce
 * @package    Scommerce_ImageOptimiser
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */
namespace Scommerce\ImageOptimiser\Logger;

use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Logger\Handler\Base;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Class Handler
 * @package Scommerce_ImageOptimiser
 */
class Handler extends Base {

     /**
     * @var string
     */
    protected $fileName = null;
    
    /**
     * const /var/log/image_optimiser
     */
    const IMAGE_OPTIMISER_LOG = '/var/log/image_optimiser';
    
    /**
     * __construct
     * 
     * @param DriverInterface $filesystem
     * @param TimezoneInterface $localeDate
     */
    public function __construct(
        DriverInterface $filesystem, 
        TimezoneInterface $localeDate
    ) {
        $currentDate = $localeDate->date()->format('Ymd');
        $this->fileName = self::IMAGE_OPTIMISER_LOG . "-$currentDate.log";
        parent::__construct($filesystem);
    }
}
