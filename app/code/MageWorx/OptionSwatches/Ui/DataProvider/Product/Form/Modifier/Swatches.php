<?php
/**
 * Copyright © 2017 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionSwatches\Ui\DataProvider\Product\Form\Modifier;

use MageWorx\OptionSwatches\Helper\Data as Helper;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\CustomOptions;
use MageWorx\OptionBase\Ui\DataProvider\Product\Form\Modifier\ModifierInterface;
use Magento\Ui\Component\Form\Element\Checkbox;
use Magento\Ui\Component\Form\Element\DataType\Text;
use Magento\Ui\Component\Form\Field;

/**
 * Data provider for "Customizable Options" panel
 */
class Swatches extends AbstractModifier implements ModifierInterface
{
    protected array $meta = [];
    protected string $form = 'product_form';
    protected Helper $helper;

    public function __construct(
        Helper $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * Get sort order of modifier to load modifiers in the right order
     *
     * @return int
     */
    public function getSortOrder()
    {
        return 60;
    }

    public function modifyData(array $data)
    {
        return $data;
    }

    public function modifyMeta(array $meta)
    {
        $this->meta = $meta;

        $this->addSelectTypes();

        return $this->meta;
    }

    protected function addSelectTypes()
    {
        $groupCustomOptionsName = CustomOptions::GROUP_CUSTOM_OPTIONS_NAME;
        $optionContainerName = CustomOptions::CONTAINER_OPTION;
        $commonOptionContainerName = CustomOptions::CONTAINER_COMMON_NAME;

        // Add fields to the option
        $optionSwatchesFields = $this->getOptionSwatchesFieldsConfig();
        $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
        [$optionContainerName]['children'][$commonOptionContainerName]['children'] = array_replace_recursive(
            $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
            [$optionContainerName]['children'][$commonOptionContainerName]['children'],
            $optionSwatchesFields
        );
    }

    /**
     * The custom option fields config
     *
     * @return array
     */
    protected function getOptionSwatchesFieldsConfig()
    {
        $fields = [];

        $fields[Helper::KEY_IS_SWATCH] = $this->getIsSwatchConfig(70);

        return $fields;
    }

    /**
     * Is Swatch Option field config
     *
     * @param $sortOrder
     * @return array
     */
    protected function getIsSwatchConfig($sortOrder)
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => __('Is Swatch'),
                        'componentType' => Field::NAME,
                        'component' => 'MageWorx_OptionSwatches/js/element/option-type-filtered-checkbox',
                        'formElement' => Checkbox::NAME,
                        'dataScope' => Helper::KEY_IS_SWATCH,
                        'dataType' => Text::NAME,
                        'sortOrder' => $sortOrder,
                        'valueMap' => [
                            'true' => Helper::IS_SWATCH_TRUE,
                            'false' => Helper::IS_SWATCH_FALSE,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Check is current modifier for the product only
     *
     * @return bool
     */
    public function isProductScopeOnly()
    {
        return false;
    }
}
