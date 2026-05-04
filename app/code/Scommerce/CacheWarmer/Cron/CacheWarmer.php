<?php
/**
 * Cache warmer  Cron job
 *
 * @category   Scommerce
 * @package    Scommerce_CacheWarmer
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */

namespace Scommerce\CacheWarmer\Cron;

use Magento\Framework\Console\Cli;
use Magento\Framework\App\State;
use Scommerce\CacheWarmer\Helper\Data;

/**
 * Class CacheWarmer
 * 
 * @package Scommerce_CacheWarmer
 */
class CacheWarmer
{
    /**
     * @var State
     */
    protected $_state = null;

    /**
     * @var Data
     */
    protected $_helper;

    /**
     * __construct
     *
     * @param State $state
     * @param Data $helper
     */
    public function __construct(
    State $state,
    Data $helper
    ) 
    {
        $this->_state  = $state;
        $this->_helper = $helper;
    }

    /**
     * Execute cron function
     */
    public function execute()
    {
        try{
            $this->_state->emulateAreaCode(
                \Magento\Framework\App\Area::AREA_FRONTEND,
                [$this, "executeCallBack"]
            );
        }
        catch(\Exception $e){
            echo $e->getMessage();
        }
        return Cli::RETURN_SUCCESS;
    }

    /**
     * @throws \Exception
     */
    public function executeCallBack() {
        if (!$this->_helper->isEnabled()) {
            return;
        }
        return $this->_helper->getUrls();
    }

}
