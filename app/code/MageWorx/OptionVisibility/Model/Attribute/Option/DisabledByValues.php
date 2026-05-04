<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionVisibility\Model\Attribute\Option;

use MageWorx\OptionVisibility\Helper\Data as Helper;
use MageWorx\OptionBase\Model\Product\Option\AbstractAttribute;

class DisabledByValues extends AbstractAttribute
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return Helper::KEY_DISABLED_BY_VALUES;
    }

    /**
     * {@inheritdoc}
     */
    public function prepareDataBeforeSave($option)
    {
        $isDisabledOption = false;
        if (is_object($option) && $option->getValues()) {
            $isDisabledOption = $this->checkDisabled($option->getValues());
        } elseif (!empty($option['values']) && is_array($option['values'])) {
            $isDisabledOption = $this->checkDisabled($option['values']);
        }
        return (int)$isDisabledOption;
    }

    /**
     * {@inheritdoc}
     */
    public function prepareDataForFrontend($object)
    {
        return [];
    }

    /**
     * Check values for Disabled flag
     * if ALL are 'disabled' - return true
     * if ANY is NOT 'disabled' - return false
     *
     * @param array $values
     * @return bool $isDisabledOption
     */
    protected function checkDisabled($values)
    {
        foreach ($values as $value) {
            if (empty($value['disabled'])) {
                return false;
            }
        }
        return true;
    }
}
