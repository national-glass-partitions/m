<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionImportExport\Plugin;

use Magento\CatalogImportExport\Model\Import\Product\Option as ImportOption;
use MageWorx\OptionImportExport\Model\MageTwo\ImportProductRegistry;

class SetProductImportFlag
{
    /**
     * @var ImportProductRegistry
     */
    protected $importProductRegistry;

    /**
     * @param ImportProductRegistry $importProductRegistry
     */
    public function __construct(
        ImportProductRegistry $importProductRegistry
    ) {
        $this->importProductRegistry = $importProductRegistry;
    }

    /**
     * @param ImportOption $subject
     * @return void
     */
    public function beforeImportData($subject)
    {
        $this->importProductRegistry->setIsOptionImport(true);
    }

    /**
     * @param ImportOption $subject
     * @return bool Result of operation.
     */
    public function afterImportData($subject, $result)
    {
        $this->importProductRegistry->setIsOptionImport(false);
        return $result;
    }
}