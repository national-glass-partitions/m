<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See https://www.mageworx.com/terms-and-conditions for license details.
 */

declare(strict_types=1);

namespace MageWorx\OptionHealthChecker\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use MageWorx\OptionHealthChecker\Api\DataCleanerModelInterface;

class DataCleaner implements DataPatchInterface
{
    private DataCleanerModelInterface $dataCleanerModel;

    public function __construct(
        DataCleanerModelInterface $dataCleanerModel
    ) {
        $this->dataCleanerModel = $dataCleanerModel;
    }

    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    public function apply(): void
    {
        $this->dataCleanerModel->setIsTablesValid(false);
        $this->dataCleanerModel->dataCleanerHandler(false);
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases(): array
    {
        return [];
    }
}
