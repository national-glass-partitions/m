<?php

/**
 * Image Optimiser MediaBackup class for creating console commands
 *
 * @category   Scommerce
 * @package    Scommerce_ImageOptimiser
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */
namespace Scommerce\ImageOptimiser\Console\Command;

use Magento\Setup\Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Magento\Framework\App\State;
use Scommerce\ImageOptimiser\Model\Download;

/**
 * $ bin/magento help scommerce:imageoptimiser:mediabackup
 * Usage:
 * scommerce:imageoptimiser:mediabackup 
 *
 * Options:
 * --help (-h)           Display this help message
 * --quiet (-q)          Do not output any message
 * --verbose (-v|vv|vvv) Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
 * --version (-V)        Display this application version
 * --ansi                Force ANSI output
 * --no-ansi             Disable ANSI output
 * --no-interaction (-n) Do not ask any interactive question
 */

/**
 * Class MediaBackup
 * @package Scommerce_ImageOptimiser;
 */
class MediaBackup extends Command
{
    /**
     * @var State
     */
    protected $_state = null;
        
    /**
     * @var Download
     */
    protected $_download;
    
    /**
     * __construct
     * 
     * @param State $state
     * @param Download $download
     */
    public function __construct(
        State $state,
        Download $download
    ){
        parent::__construct();
        $this->_state = $state;
        $this->_download = $download;
    }
    
    /**
     * CLI command configure
     */
    protected function configure()
    {
        $this->setName('scommerce:imageoptimiser:mediabackup')
            ->setDescription(__('Image Optimiser to optimise media images'))
            ->setDefinition([
                new InputArgument(
                        '',
                    InputArgument::OPTIONAL

                )
            ]);
        parent::configure();
    }
    
    /**
     * execute
     * 
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws
     */
    protected function execute(
        InputInterface $input, 
        OutputInterface $output
    ) {
        try {
            $this->_state->setAreaCode('adminhtml');
        } catch (Exception $e) {
            $output->writeln($e->getMessage());
        }
        try {
            $output->write("Creating copy of the media.");
            $this->_download->downloadMedia();
            $output->writeln("  => updated");
        } catch (Exception $e) {
            $output->writeln($e->getMessage());
        }
    }

}
