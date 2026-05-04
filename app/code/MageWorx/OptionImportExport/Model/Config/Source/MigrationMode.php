<?php
/**
 * Copyright ©  MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionImportExport\Model\Config\Source;

class MigrationMode implements \Magento\Framework\Data\OptionSourceInterface
{
    const MIGRATION_MODE_DELETE_ALL_OPTIONS                      = 'full_reset';
    const MIGRATION_MODE_DELETE_OPTIONS_ON_INTERSECTING_PRODUCTS = 'reset_intersecting_products';
    const MIGRATION_MODE_ADD_OPTIONS_TO_THE_END                  = 'add';

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => static::MIGRATION_MODE_DELETE_ALL_OPTIONS,
                'label' => static::getMigrationModeDeleteAllOptionsLabel()
            ],
            [
                'value' => static::MIGRATION_MODE_DELETE_OPTIONS_ON_INTERSECTING_PRODUCTS,
                'label' => static::getMigrationModeDeleteOptionsOnIntersectingProductsLabel()
            ],
            [
                'value' => static::MIGRATION_MODE_ADD_OPTIONS_TO_THE_END,
                'label' => static::getMigrationModeAddOptionsToTheEndLabel()
            ]
        ];
    }

    public static function getMigrationModeDeleteAllOptionsLabel()
    {
        return __('Remove all customizable options on Magento 2 store');
    }

    public static function getMigrationModeDeleteOptionsOnIntersectingProductsLabel()
    {
        return __('Remove customizable options only for products, you are importing options for');
    }

    public static function getMigrationModeAddOptionsToTheEndLabel()
    {
        return __('Keep existing customizable options and add options from the imported files');
    }
}
