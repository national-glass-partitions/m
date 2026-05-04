<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionTemplates\Ui\DataProvider\Group\Form\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\CustomOptions as OriginalCustomOptions;
use Magento\Ui\Component\Form\Fieldset;

class CustomOptions extends OriginalCustomOptions
{
    const GROUP_CUSTOM_OPTIONS_SCOPE = 'data.mageworx_optiontemplates_group';

    const FORM_NAME           = 'mageworx_optiontemplates_group';
    const DATA_SOURCE_DEFAULT = 'mageworx_optiontemplates_group';
    const DATA_SCOPE_PRODUCT  = 'data.mageworx_optiontemplates_group';

    /**
     * Adds option & values data
     *
     * @param array $data
     * @return array
     */
    public function modifyData(array $data)
    {
        $index          = 0;
        $options        = [];
        $productOptions = [];
        if ($this->locator->getProduct()->getOptions()) {
            $productOptions = $this->locator->getProduct()->getOptions();
        }
        /** @var \Magento\Catalog\Model\Product\Option $option */
        foreach ($productOptions as $option) {
            $optionData      = $option->getData();
            $options[$index] = $this->formatPriceByPath(static::FIELD_PRICE_NAME, $optionData);
            $values          = [];
            if ($option->getValues()) {
                $values = $option->getValues();
            }
            /** @var \Magento\Catalog\Model\Product\Option $value */
            foreach ($values as $value) {
                $valueData                                        = $value->getData();
                $options[$index][static::GRID_TYPE_SELECT_NAME][] = $this->formatPriceByPath(
                    static::FIELD_PRICE_NAME,
                    $valueData
                );
            }
            $index++;
        }

        return array_replace_recursive(
            $data,
            [
                $this->locator->getProduct()->getId() => [
                    static::DATA_SOURCE_DEFAULT => [
                        static::FIELD_ENABLE      => 1,
                        static::GRID_OPTIONS_NAME => $options,
                    ],
                ],
            ]
        );
    }

    /**
     * Create Custom Options panel
     *
     * @return $this
     */
    protected function createCustomOptionsPanel()
    {
        $customOptionsPanelChildren = [
            static::CONTAINER_HEADER_NAME => $this->getHeaderContainerConfig(10),
            static::FIELD_ENABLE          => $this->getEnableFieldConfig(20),
            static::GRID_OPTIONS_NAME     => $this->getOptionsGridConfig(30),
        ];

        $customOptionsPanel = [
            static::GROUP_CUSTOM_OPTIONS_NAME => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'label'         => __('Customizable Options'),
                            'componentType' => Fieldset::NAME,
                            'dataScope'     => static::GROUP_CUSTOM_OPTIONS_SCOPE,
                            'collapsible'   => true,
                            'sortOrder'     => static::GROUP_CUSTOM_OPTIONS_DEFAULT_SORT_ORDER
                        ],
                    ],
                ],
                'children'  => $customOptionsPanelChildren,
            ],
        ];

        $this->meta = array_replace_recursive(
            $this->meta,
            $customOptionsPanel
        );

        return $this;
    }

    /**
     * Get config for header container without import button
     *
     * @param int $sortOrder
     * @return array
     */
    protected function getHeaderContainerConfig($sortOrder)
    {
        $result = parent::getHeaderContainerConfig($sortOrder);
        if (!empty($result['children'][OriginalCustomOptions::BUTTON_IMPORT])) {
            unset($result['children'][OriginalCustomOptions::BUTTON_IMPORT]);
        }

        return $result;
    }
}
