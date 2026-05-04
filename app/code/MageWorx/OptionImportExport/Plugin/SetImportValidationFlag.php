<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionImportExport\Plugin;

use Magento\ImportExport\Model\Import\Entity\AbstractEntity;
use MageWorx\OptionImportExport\Model\MageTwo\ImportProductRegistry;

class SetImportValidationFlag
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
     * @param AbstractEntity $subject
     * @return void
     */
    public function beforeValidateData($subject)
    {
        $this->importProductRegistry->setIsImportValidation(true);
    }

    /**
     * @param AbstractEntity $subject
     * @param \Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface $result
     * @return \Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface
     */
    public function afterValidateData($subject, $result)
    {
        $this->importProductRegistry->setIsImportValidation(false);
        return $result;
    }
}