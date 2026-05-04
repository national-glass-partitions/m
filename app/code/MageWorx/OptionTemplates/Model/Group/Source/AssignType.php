<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionTemplates\Model\Group\Source;

use MageWorx\OptionTemplates\Model\Source;

/**
 * Used in creating options for config value selection
 *
 */
class AssignType extends Source
{
    const ASSIGN_BY_GRID = 1;
    const ASSIGN_BY_IDS = 2;
    const ASSIGN_BY_SKUS = 3;

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::ASSIGN_BY_IDS,
                'label' => __('Product IDs'),
            ],
            [
                'value' => self::ASSIGN_BY_SKUS,
                'label' => __('Product SKUs'),
            ],
        ];
    }
}
