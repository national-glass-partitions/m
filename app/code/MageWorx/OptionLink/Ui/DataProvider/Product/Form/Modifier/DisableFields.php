<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionLink\Ui\DataProvider\Product\Form\Modifier;

use \MageWorx\OptionLink\Helper\Attribute as HelperAttribute;
use \MageWorx\OptionLink\Model\ResourceModel\Product\Option\Value\CollectionUpdater;
use \MageWorx\OptionBase\Ui\DataProvider\Product\Form\Modifier\ModifierInterface;
use \Magento\Catalog\Model\Locator\LocatorInterface;
use \Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use \Magento\Store\Model\StoreManagerInterface;
use \Magento\Framework\Stdlib\ArrayManager;
use \Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\CustomOptions;
use \Magento\Ui\Component\Form\Element\DataType\Number;
use \Magento\Ui\Component\Form\Element\Hidden;
use \Magento\Ui\Component\Form\Field;
use MageWorx\OptionFeatures\Ui\DataProvider\Product\Form\Modifier\ValueSettings as ValueSettingsModifier;
use MageWorx\OptionFeatures\Helper\Data as FeaturesHelper;

/**
 * Class DisableFields. Update custom options grid in product edit page.
 * Add 'sku_is_valid' hidden field.
 * Update 'disabled' attribute on some option values fields.
 */
class DisableFields extends AbstractModifier implements ModifierInterface
{
    protected HelperAttribute $helperAttribute;
    protected LocatorInterface $locator;
    protected StoreManagerInterface $storeManager;
    protected ArrayManager $arrayManager;
    protected array $meta = [];
    protected array $ignoredFields = [FeaturesHelper::KEY_COST, FeaturesHelper::KEY_WEIGHT];

    /**
     * DisableFields constructor.
     *
     * @param ArrayManager $arrayManager
     * @param StoreManagerInterface $storeManager
     * @param LocatorInterface $locator
     * @param HelperAttribute $helperAttribute
     */
    public function __construct(
        ArrayManager $arrayManager,
        StoreManagerInterface $storeManager,
        LocatorInterface $locator,
        HelperAttribute $helperAttribute,
        array $ignoredFields = []
    ) {
        $this->arrayManager    = $arrayManager;
        $this->storeManager    = $storeManager;
        $this->locator         = $locator;
        $this->helperAttribute = $helperAttribute;
        $this->ignoredFields   = array_merge($this->ignoredFields, $ignoredFields);
    }

    /**
     * Get sort order of modifier to load modifiers in the right order
     *
     * @return int
     */
    public function getSortOrder()
    {
        return 200;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        $linkedFields = $this->helperAttribute->getConvertedAttributesToFields();
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $this->locator->getProduct();

        if (!$product || !$product->getId()) {
            $key = 'product';
            if (isset($data['']['mageworx_optiontemplates_group'])) {
                $key = 'mageworx_optiontemplates_group';
            }
            $data[''][$key]['option_link_fields'] = $linkedFields;

            return $data;
        }

        return array_replace_recursive(
            $data,
            [
                $product->getId() => [
                    static::DATA_SOURCE_DEFAULT => [
                        'option_link_fields' => $linkedFields
                    ],
                ],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        $this->meta = $meta;
        $fields     = $this->helperAttribute->getConvertedAttributesToFields();

        $this->addSkuIsValid();

        foreach ($fields as $field) {
            $this->disableFieldLinkedBySku($field);
        }

        return $this->meta;
    }

    /**
     * Add 'sku_is_valid' hidden field
     * to option values grid.
     *
     * return void
     */
    protected function addSkuIsValid()
    {
        $groupCustomOptionsName = CustomOptions::GROUP_CUSTOM_OPTIONS_NAME;
        $optionContainerName    = CustomOptions::CONTAINER_OPTION;

        // Add fields to the values
        $skuIsValidConfig = $this->getSkuIsValidConfig(250);

        $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
        [$optionContainerName]['children']['values']['children']['record']['children'] = array_replace_recursive(
            $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
            [$optionContainerName]['children']['values']['children']['record']['children'],
            $skuIsValidConfig
        );
    }

    /**
     * Retrieve data of 'sku_is_valid' hidden field.
     *
     * @param int $sortOrder
     * @return array
     */
    protected function getSkuIsValidConfig($sortOrder)
    {
        $field[CollectionUpdater::KEY_FIELD_SKU_IS_VALID] = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => Field::NAME,
                        'formElement'   => Hidden::NAME,
                        'dataScope'     => CollectionUpdater::KEY_FIELD_SKU_IS_VALID,
                        'dataType'      => Number::NAME,
                        'sortOrder'     => $sortOrder,
                    ],
                ],
            ],
        ];

        return $field;
    }

    /**
     * Update 'disabled' attribute on option values
     * fields linked by SKU.
     *
     * @param string $field
     * return void
     */
    protected function disableFieldLinkedBySku($field)
    {
        if (in_array($field, $this->ignoredFields)) {
            return;
        }

        $groupCustomOptionsName = CustomOptions::GROUP_CUSTOM_OPTIONS_NAME;
        $optionContainerName    = CustomOptions::CONTAINER_OPTION;

        // Add field to the values
        $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
        [$optionContainerName]['children']['values']['children']['record']['children']
        [$field]['arguments']['data']['config'] = array_replace_recursive(

            $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
            [$optionContainerName]['children']['values']['children']['record']['children']
            [$field]['arguments']['data']['config'],
            [
                'component' => 'MageWorx_OptionLink/js/components/disable-field-handler',
                'imports'   => [
                    'setDisabled'   => '${ $.provider }:${ $.parentScope }.' . CollectionUpdater::KEY_FIELD_SKU_IS_VALID,
                    '__disableTmpl' => ['setDisabled' => false]
                ],
            ]
        );
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
