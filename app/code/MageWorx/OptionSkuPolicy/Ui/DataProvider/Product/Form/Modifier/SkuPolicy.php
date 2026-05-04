<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionSkuPolicy\Ui\DataProvider\Product\Form\Modifier;

use MageWorx\OptionSkuPolicy\Helper\Data as Helper;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\CustomOptions;
use MageWorx\OptionBase\Ui\DataProvider\Product\Form\Modifier\ModifierInterface;
use Magento\Ui\Component\Container;
use Magento\Ui\Component\Form\Element\Select;
use Magento\Ui\Component\Form\Element\DataType\Text;
use Magento\Ui\Component\Form\Field;
use Magento\Catalog\Model\Locator\LocatorInterface;
use MageWorx\OptionSkuPolicy\Model\Config\Source\SkuPolicyMode as SourceConfig;

/**
 * Data provider for "Customizable Options" panel
 */
class SkuPolicy extends AbstractModifier implements ModifierInterface
{
    protected array $meta = [];
    protected Helper $helper;
    protected LocatorInterface $locator;
    protected SourceConfig $sourceConfig;

    public function __construct(
        Helper $helper,
        SourceConfig $sourceConfig,
        LocatorInterface $locator
    ) {
        $this->helper       = $helper;
        $this->sourceConfig = $sourceConfig;
        $this->locator      = $locator;
    }

    /**
     * Get sort order of modifier to load modifiers in the right order
     *
     * @return int
     */
    public function getSortOrder()
    {
        return 70;
    }

    public function modifyData(array $data)
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $this->locator->getProduct();
        if (!$product || !$product->getId()) {
            return $data;
        }

        return array_replace_recursive(
            $data,
            [
                $product->getId() => [
                    static::DATA_SOURCE_DEFAULT => [
                        Helper::KEY_SKU_POLICY => $product->getData(Helper::KEY_SKU_POLICY)
                    ],
                ],
            ]
        );
    }

    public function modifyMeta(array $meta)
    {
        $this->meta = $meta;

        if ($this->helper->isEnabledSkuPolicy()) {
            $this->addSkuPolicy();
        }

        return $this->meta;
    }

    protected function addSkuPolicy()
    {
        $groupCustomOptionsName    = CustomOptions::GROUP_CUSTOM_OPTIONS_NAME;
        $optionContainerName       = CustomOptions::CONTAINER_OPTION;
        $commonOptionContainerName = CustomOptions::CONTAINER_COMMON_NAME;

        // Add field to the options
        $optionSkuPolicyFields                                                     = $this->getOptionSkuPolicyFieldsConfig(
        );
        $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
        [$optionContainerName]['children'][$commonOptionContainerName]['children'] = array_replace_recursive(
            $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
            [$optionContainerName]['children'][$commonOptionContainerName]['children'],
            $optionSkuPolicyFields
        );

        // Add field to the product/template
        $productSkuPolicyFields                          = $this->getProductSkuPolicyFieldsConfig();
        $this->meta[$groupCustomOptionsName]['children'] = array_replace_recursive(
            $this->meta[$groupCustomOptionsName]['children'],
            $productSkuPolicyFields
        );
    }

    /**
     * Get Sku Policy Field config for product
     *
     * @return array
     */
    protected function getProductSkuPolicyFieldsConfig()
    {
        $children                         = [];
        $children[Helper::KEY_SKU_POLICY] = $this->getSkuPolicyConfig(13);

        $fields = [
            'global_config_container' => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'componentType'     => Container::NAME,
                            'formElement'       => Container::NAME,
                            'component'         => 'Magento_Ui/js/form/components/group',
                            'breakLine'         => false,
                            'showLabel'         => false,
                            'additionalClasses' =>
                                'admin__field-control admin__control-grouped admin__field-group-columns',
                            'sortOrder'         => 10,
                        ],
                    ],
                ],
                'children'  => $children
            ],
        ];
        return $fields;
    }

    /**
     * Get Sku Policy Field config for options
     *
     * @return array
     */
    protected function getOptionSkuPolicyFieldsConfig()
    {
        $fields = [];

        $fields[Helper::KEY_SKU_POLICY] = $this->getSkuPolicyConfig(73);

        return $fields;
    }

    /**
     * Is Swatch Option field config
     *
     * @param int $sortOrder
     * @param bool $isOption
     * @return array
     */
    protected function getSkuPolicyConfig($sortOrder)
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label'         => __('SKU Policy'),
                        'componentType' => Field::NAME,
                        'component'     => 'Magento_Ui/js/form/element/select',
                        'formElement'   => Select::NAME,
                        'dataScope'     => Helper::KEY_SKU_POLICY,
                        'dataType'      => Text::NAME,
                        'disableLabel'  => true,
                        'multiple'      => false,
                        'options'       => $this->sourceConfig->getOptions(),
                        'sortOrder'     => $sortOrder,
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
