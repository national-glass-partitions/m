<?php
/**
 * Cache warmer console command for cms page cache
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
 * Class CmsPageCacheWarmer
 * 
 * @package Scommerce_CacheWarmer
 */
class CmsPageCacheWarmer extends Command
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
        parent::__construct('scommerce:cachewarmer:cmspage');
    }
    /**
     * Configure cli command.
     */
    protected function configure()
    {
        $this->setName('scommerce:cachewarmer:cmspage')
            ->setDescription('Run the Cms page cache and cache all available pages in the store.');
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
            $this->_helper->getUrls('cms-page');
            $output->writeln('Cms page cache has been generated.');
        } else {
            $output->writeln('Please check module is enabled and concurrent request should be more then zero(0).');
        }
        return $this;
    }
}
