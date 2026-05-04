<?php
/**
 * Cache warmer console command for all page caches
 *
 * @category   Scommerce
 * @package    Scommerce_Cachewarmer
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */

namespace Scommerce\CacheWarmer\Console\Command;

use Scommerce\CacheWarmer\Cron\CacheWarmer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\Console\Cli;

/**
 * Class AllCacheWarmer
 * 
 * @package Scommerce_CacheWarmer
 */
class AllCacheWarmer extends Command
{
    /**
     * @var CacheWarmer
     */
    protected $_cacheWarmer;

    /**
     * All Cache Warmer Command constructor.
     * @param CacheWarmer $cacheWarmer
     */
    public function __construct(
        CacheWarmer $cacheWarmer
    ) {
        parent::__construct();
        $this->_cacheWarmer = $cacheWarmer;
    }

    /**
     * Configure cli command.
     */
    protected function configure()
    {
        $this->setName('scommerce:cachewarmer:all')
            ->setDescription('Run all the cache.');
    }

    /**
     * Execute cli command
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return $this|int|null
     * @throws LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_cacheWarmer->execute();
        return Cli::RETURN_SUCCESS;
    }
}
