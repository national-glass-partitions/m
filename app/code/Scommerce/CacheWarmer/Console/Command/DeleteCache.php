<?php
/**
 * Cache warmer console command for product page cache
 *
 * @category   Scommerce
 * @package    Scommerce_Cachewarmer
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */

namespace Scommerce\CacheWarmer\Console\Command;

use Scommerce\CacheWarmer\Helper\Data;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteCache extends Command
{
    /**
     * @var State
     */
    protected $appState;
    
    /**
     * @var helper
     */
    protected $_helper;

    /**
     * WarmCache constructor.
     * @param State $appState
     */
    public function __construct(
        State $appState,
        Data  $helper
    ) {
        $this->appState = $appState;
        $this->_helper = $helper;
        parent::__construct('scommerce:cachewarmer:delete');
    }

    /**
     * Configure cli command.
     */
    protected function configure()
    {
        $this->setName('scommerce:cachewarmer:delete')
            ->setDescription('Delete all the cache.');
    }

    /**
     * Execute cli command
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return $this|int|null
     * @throws LocalizedException
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $this->appState->setAreaCode('adminhtml');
            if ($this->_helper->isEnabled()) {
                $this->_helper->cacheClear();
                $output->writeln('Cache clean process finished.');
            } else {
                $output->writeln('Please check module is enabled and you have select cache regeneration.');
            }
      
        return $this;  
    }
}

     