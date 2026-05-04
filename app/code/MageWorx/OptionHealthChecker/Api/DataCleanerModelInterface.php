<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionHealthChecker\Api;

interface DataCleanerModelInterface
{
    /**
     * Process of cleaning garbage data with APO attributes from the database
     *
     * @param bool $isAnalyzeData
     * @return array
     */
    public function dataCleanerHandler(bool $isAnalyzeData): array;

    public function setIsTablesValid(bool $value): void;
}
