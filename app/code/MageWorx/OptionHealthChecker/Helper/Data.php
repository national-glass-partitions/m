<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See https://www.mageworx.com/terms-and-conditions for license details.
 */

declare(strict_types=1);

namespace MageWorx\OptionHealthChecker\Helper;

use Magento\Framework\App\Config\Storage\WriterInterface;

class Data
{
    protected WriterInterface $configWriter;

    /**
     * Data constructor.
     *
     * @param WriterInterface $configWriter
     */
    public function __construct(
        WriterInterface $configWriter
    ) {
        $this->configWriter = $configWriter;
    }

    public function isPlural(int $count): bool
    {
        return $count > 1;
    }

    public function setIsTablesValid(bool $value): void
    {
        $this->configWriter->save('mageworx_apo/optionhealthchecker/is_valid', $value);
    }
}
