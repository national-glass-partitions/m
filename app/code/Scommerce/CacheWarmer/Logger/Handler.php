<?php
/**
 * Cache Warmer Logger Handler class for creating the log file
 *
 * @category   Scommerce
 * @package    Scommerce_CacheWarmer
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */
namespace Scommerce\CacheWarmer\Logger;

use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Logger\Handler\Base;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Class Handler
 * @package Scommerce_CacheWarmer
 */
class Handler extends Base {

     /**
     * @var string
     */
    protected $fileName = null;
    
    /**
     * const /var/log/image_cachewarmer
     */
    const IMAGE_OPTIMISER_LOG = '/var/log/cachewarmer';
    
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
