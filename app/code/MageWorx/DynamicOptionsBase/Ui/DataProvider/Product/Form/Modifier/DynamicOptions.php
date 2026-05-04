<?php
/**
 * Copyright © MageWorx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MageWorx\DynamicOptionsBase\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use MageWorx\DynamicOptionsBase\Api\Data\DynamicOptionInterface;

class DynamicOptions extends AbstractModifier
{
    const GROUP_CUSTOM_OPTIONS_PREVIOUS_NAME       = 'search-engine-optimization';
    const GROUP_DYNAMIC_OPTIONS_DEFAULT_SORT_ORDER = 31;
    const GROUP_DYNAMIC_OPTIONS_NAME               = 'mageworx-dynamic-options';

    protected LocatorInterface $locator;
    protected array $meta = [];

    public function __construct(
        LocatorInterface $locator
    ) {
        $this->locator = $locator;
    }

    /**
     * @param array $data
     * @return array
     */
    public function modifyData(array $data)
    {
        $product = $this->locator->getProduct();
        /** @var DynamicOptionInterface $dynamicOptions */
        $dynamicOptions = $product->getMageworxDynamicOptions();
        $ids            = [];

        if (!$dynamicOptions) {
            return $data;
        }

        $key = 0;
        /** @var DynamicOptionInterface $dynamicOption */
        foreach ($dynamicOptions as $dynamicOption) {
            array_push($ids, (string)$dynamicOption->getOptionId());

            $data[$product->getId()]['product']
            ['mageworx_dynamic_options_data][' . $key . '][max_value'] = $dynamicOption->getMaxValue();

            $data[$product->getId()]['product']
            ['mageworx_dynamic_options_data][' . $key . '][min_value'] = $dynamicOption->getMinValue();

            $data[$product->getId()]['product']
            ['mageworx_dynamic_options_data][' . $key . '][step'] = $dynamicOption->getStep();

            $data[$product->getId()]['product']
            ['mageworx_dynamic_options_data][' . $key . '][measurement_unit'] = $dynamicOption->getMeasurementUnit();
            $key++;
        }

        $data[$product->getId()]['product']['mageworx_dynamic_options'] = $ids;

        return $data;
    }

    /**
     * @param array $meta
     * @return array
     */
    public function modifyMeta(array $meta)
    {
        $this->meta = $meta;

        $this->updateDynamicOptionsPanel();

        return $this->meta;
    }

    /**
     * @return $this
     */
    protected function updateDynamicOptionsPanel()
    {
        $this->meta = array_replace_recursive(
            $this->meta,
            [
                static::GROUP_DYNAMIC_OPTIONS_NAME => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'label'     => __('Mageworx Dynamic Options'),
                                'sortOrder' => $this->getNextGroupSortOrder(
                                    $this->meta,
                                    static::GROUP_CUSTOM_OPTIONS_PREVIOUS_NAME,
                                    static::GROUP_DYNAMIC_OPTIONS_DEFAULT_SORT_ORDER
                                ),
                            ],
                        ],
                    ],
                    'children'  => $this->getFieldsForFieldset()
                ]
            ]
        );

        return $this;
    }

    /**
     * @return array
     */
    protected function getFieldsForFieldset()
    {
        $children = [];

        $children['mageworx_dynamic_options'] = [
            'arguments' => [
                'data' => [
                    'config'   => [
                        'sortOrder' => 1,
                        'notice'    => __(
                            'Choose the options you want to apply the dynamic pricing functionality to. Selected options will be used to calculate the final price. You can select up to 3 options. Leave empty to disable the price per measurement functionality for the current product.
    Note: Only options of the "Text field" type will appear here.')
                    ],
                ],
            ],
        ];

        $children[DynamicOptionInterface::PRICE_PER_UNIT] = [
            'arguments' => [
                'data' => [
                    'config'   => [
                        'notice'     => __('The price for 1 measurement unit'),
                        'sortOrder'  => 20,
                        'validation' => [
                            'validate-number'          => true,
                            'validate-zero-or-greater' => true
                        ],
                        'addbefore' => $this->locator->getStore()->getBaseCurrency()->getCurrencySymbol()
                    ]
                ],
            ],
        ];

        return $children;
    }
}
