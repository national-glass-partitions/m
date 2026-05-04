<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionTemplates\Model\Group\Source;

use MageWorx\OptionTemplates\Model\Group;
use MageWorx\OptionTemplates\Model\Source;

class IsActive extends Source
{
    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => Group::VISIBLE_SHOW,
                'label' => __('Yes')
            ],[
                'value' => Group::VISIBLE_HIDE,
                'label' => __('No')
            ],
        ];
    }
}
