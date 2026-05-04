<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\DynamicOptionsBase\Model;

use Magento\Framework\Data\OptionSourceInterface;

abstract class Source implements OptionSourceInterface
{
    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    abstract public function toOptionArray(): array;

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray(): array
    {
        $tmpOptions = $this->toOptionArray();
        $options    = [];
        foreach ($tmpOptions as $option) {
            $options[$option['value']] = $option['label'];
        }

        return $options;
    }
}
