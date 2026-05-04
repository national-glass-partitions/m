<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionImportExport\Model\Product\Validator;

use Magento\CatalogImportExport\Model\Import\Product\RowValidatorInterface;
use Magento\CatalogImportExport\Model\Import\Product\Validator\AbstractImportValidator;
use MageWorx\OptionImportExport\Model\MageTwo\ImportProductRegistry;

class Option extends AbstractImportValidator implements RowValidatorInterface
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
     * Check structure integrity of options
     *
     * @param array $value
     * @return bool
     */
    public function isValid($value)
    {
        $this->_clearMessages();
        $valid = true;

        if (!$this->importProductRegistry->isProductAPOImport()) {
            return $valid;
        }

        $optionTypeFieldName = 'custom_option_type';
        $optionTitleFieldName = 'custom_option_title';
        $valueTitleFieldName = 'custom_option_row_title';
        if (!$this->importProductRegistry->getIsImportValidation()
            || !isset($value[$optionTitleFieldName])
        ) {
            return $valid;
        }

        if ((!empty($value[$optionTitleFieldName]) || $value[$optionTitleFieldName] === '0')
            && (empty($value[$valueTitleFieldName]) && $value[$valueTitleFieldName] !== '0')
            && (empty($value[$optionTypeFieldName]) || in_array(
                    $value[$optionTypeFieldName],
                    ['drop_down', 'checkbox', 'radio', 'multiple']
                )
            )
        ) {
            $this->_addMessages(
                [
                    "Selectable option doesn't contain any value"
                ]
            );
            return false;
        }

        return $valid;
    }
}
