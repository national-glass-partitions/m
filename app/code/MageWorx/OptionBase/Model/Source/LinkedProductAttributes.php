<?php
/**
 * Copyright © 2017 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionBase\Model\Source;

/**
 * Class LinkedProductAttributes
 */
class LinkedProductAttributes
{
    protected array $linkedAttributes = [];

    /**
     * LinkedProductAttributes constructor.
     *
     * @param array $linkedAttributes
     */
    public function __construct(
        $linkedAttributes = []
    ) {
        $this->linkedAttributes = $linkedAttributes;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = [
            ['value' => \Magento\Catalog\Api\Data\ProductAttributeInterface::CODE_NAME, 'label' => __('Name')],
            ['value' => \Magento\Catalog\Api\Data\ProductAttributeInterface::CODE_PRICE, 'label' => __('Price')],
        ];

        foreach ($this->linkedAttributes as $attribute) {
            $label     = implode(' ', array_map('ucfirst', explode('_', $attribute)));
            $options[] = ['value' => $attribute, 'label' => __($label)];
        }

        return $options;
    }
}
