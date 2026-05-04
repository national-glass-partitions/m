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

/**
 * Class ProductPageCacheWarmer
 * 
 * @package Scommerce_CacheWarmer
 */
class ProductPageCacheWarmer extends Command
{
    /**
     * @var State
     */
    protected $_appState;
    
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
        $this->_appState = $appState;
        $this->_helper = $helper;
        parent::__construct('scommerce:cachewarmer:product');
    }

    /**
     * Configure cli command.
     */
    protected function configure()
    {
        $this->setName('scommerce:cachewarmer:product')
            ->setDescription('Run the product page cache and cache all available pages in the store.');
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
        $this->_appState->setAreaCode('adminhtml'); 
        if ($this->_helper->isEnabled() && $this->_helper->getConcurrentRequest() > 0) {
            $this->_helper->getUrls('product');
            $output->writeln('Product page cache process has been finished.');
        } else {
            $output->writeln('Please check module is enabled and concurrent request should be more then zero(0).');
        }
        return $this;
    }
}
