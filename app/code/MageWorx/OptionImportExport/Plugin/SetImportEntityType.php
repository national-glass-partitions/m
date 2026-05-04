<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionImportExport\Plugin;

use Magento\ImportExport\Model\Import;
use MageWorx\OptionImportExport\Model\MageTwo\ImportProductRegistry;

class SetImportEntityType
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
     * Set import entity type to registry
     *
     * @param Import $import
     * @return void
     */
    public function beforeImportSource(Import $import)
    {
        $this->importProductRegistry->setImportEntityType($import->getDataSourceModel()->getEntityTypeCode());
    }
}