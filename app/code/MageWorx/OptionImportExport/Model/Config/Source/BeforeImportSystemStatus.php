<?php
/**
 * Copyright ©  MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionImportExport\Model\Config\Source;

class BeforeImportSystemStatus implements \Magento\Framework\Data\OptionSourceInterface
{
    const BEFORE_IMPORT_SYSTEM_STATUS_OPTIONS_FREE    = 'options_free';
    const BEFORE_IMPORT_SYSTEM_STATUS_NO_INTERSECTION = 'no_intersection';
    const BEFORE_IMPORT_SYSTEM_STATUS_INTERSECTION    = 'intersection';

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => static::BEFORE_IMPORT_SYSTEM_STATUS_OPTIONS_FREE,
                'label' => __('Magento installation is options free')
            ],
            [
                'value' => static::BEFORE_IMPORT_SYSTEM_STATUS_NO_INTERSECTION,
                'label' => __("Products from imported files don't have customizable options in Magento")
            ],
            [
                'value' => static::BEFORE_IMPORT_SYSTEM_STATUS_INTERSECTION,
                'label' => __("Customizable options from imported files intersect with customizable options in Magento")
            ]
        ];
    }
}
