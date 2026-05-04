<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See https://www.mageworx.com/terms-and-conditions for license details.
 */

declare(strict_types=1);

namespace MageWorx\OptionHealthChecker\Console;

use Magento\Framework\Console\Cli;
use MageWorx\OptionHealthChecker\Api\DataCleanerModelInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AnalyzeDatabaseData extends Command
{
    protected DataCleanerModelInterface $dataCleanerModel;

    /**
     * AnalyzeDataBaseData constructor.
     *
     * @param DataCleanerModelInterface $dataCleanerModel
     */
    public function __construct(
        DataCleanerModelInterface $dataCleanerModel
    ) {
        $this->dataCleanerModel = $dataCleanerModel;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('mageworx:apo:analyze-data');
        $this->setDescription('Analyze database option tables');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $analyzedDataReportArray = $this->dataCleanerModel->dataCleanerHandler(true);

            foreach ($analyzedDataReportArray as $analyzedReport) {
                $output->writeln($analyzedReport);
            }

            return Cli::RETURN_SUCCESS;
        } catch (\Exception $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');

            return Cli::RETURN_FAILURE;
        }
    }
}
