<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionInventory\Model\Source\Report;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class ManageStock
 * @package MageWorx\OptionInventory\Model\Source\Report
 */
class ManageStock implements OptionSourceInterface
{
    /**
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 0, 'label' => __('No')],
            ['value' => 1, 'label' => __('Yes')],
        ];
    }
}
