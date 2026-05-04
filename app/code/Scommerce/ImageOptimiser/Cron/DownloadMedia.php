<?php
/**
 * Image Optimiser DownloadMedia class for Setting the Cron job
 *
 * @category   Scommerce
 * @package    Scommerce_ImageOptimiser
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */

namespace Scommerce\ImageOptimiser\Cron;

use Scommerce\ImageOptimiser\Model\Download;

/**
 * Class UpdateMedia
 * @package Scommerce_ImageOptimiser
 */
class DownloadMedia
{

    /**
     * @var Download
     */
    public $_download;

    /**
     * __construct
     *
     * @param Download $download
     */
    public function __construct(
        Download $download

    )
    {
        $this->_download = $download;
    }

    /**
     * Execute cron function
     */
    public function execute()
    {
        $this->_download->downloadMedia();
    }

}

