<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionTemplates\Ui\DataProvider\Group\Form\Modifier;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Framework\UrlInterface;
use Magento\Ui\Component\Container;
use Magento\Ui\Component\Form\Element\DataType\Number;
use Magento\Ui\Component\Form\Element\DataType\Text;
use Magento\Ui\Component\Form\Element\RadioSet;
use Magento\Ui\Component\Form\Element\Textarea;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\Component\Form\Fieldset;
use MageWorx\OptionTemplates\Model\Group\Source\AssignType as AssignTypeOptions;

/**
 * Data provider for products fieldset panel
 */
class Products extends AbstractModifier
{

    const FIELD_PRODUCT_RELATIONS_NAME = 'relation';
    const FIELD_ASSIGN_TYPE_NAME = 'assign_type';
    const FIELD_HEADER_NAME = 'header';
    const FIELD_COMMENT_NAME = 'comment';

    const FIELD_PRODUCTS_GRID_NAME = 'in_products';
    const FIELD_PRODUCTS_ID_NAME = 'productids';
    const FIELD_PRODUCTS_SKU_NAME = 'productskus';

    /**
     * @var LocatorInterface
     */
    protected $locator;

    /**
     * @var ArrayManager
     */
    protected $arrayManager;

    /**
     *
     * @var AssignTypeOptions
     */
    protected $assignTypeOptions;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var array
     */
    protected $meta = [];

    /**
     * @param LocatorInterface $locator
     * @param ArrayManager $arrayManager
     * @param AssignTypeOptions $assignTypeOptions
     */
    public function __construct(
        LocatorInterface $locator,
        ArrayManager $arrayManager,
        AssignTypeOptions $assignTypeOptions,
        UrlInterface $urlBuilder
    ) {
        $this->locator = $locator;
        $this->arrayManager = $arrayManager;
        $this->assignTypeOptions = $assignTypeOptions;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        $this->meta = $meta;
        $this->addProductsFieldset();

        return $this->meta;
    }

    protected function addProductsFieldset()
    {
        $this->meta[static::FIELD_PRODUCT_RELATIONS_NAME] = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => Fieldset::NAME,
                        'label' => __('%1', 'Add Products by ID/SKU'),
                        'collapsible' => true,
                        'dataScope' => self::DATA_SCOPE_GROUP,
                        'sortOrder' => 20,
                    ],
                ],
            ],
            'children' => [
                static::FIELD_HEADER_NAME => $this->getHeaderConfig(10),
                static::FIELD_ASSIGN_TYPE_NAME => $this->getAssignTypeConfig(20),
                static::FIELD_PRODUCTS_ID_NAME => $this->getProductsIdConfig(40),
                static::FIELD_PRODUCTS_SKU_NAME => $this->getProductsSkuConfig(50),
            ],
        ];
    }

    /**
     * Header container config (with comment)
     *
     * @param $sortOrder
     * @return array
     */
    protected function getHeaderConfig($sortOrder)
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => null,
                        'formElement' => Container::NAME,
                        'componentType' => Container::NAME,
                        'template' => 'ui/form/components/complex',
                        'sortOrder' => $sortOrder,
                        'content' => null // Add comments here
                    ],
                ],
            ],
            'children' => [],
        ];
    }

    /**
     * Radio buttons for assign type select type
     *
     * @param $sortOrder
     * @return array
     */
    protected function getAssignTypeConfig($sortOrder)
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => __('Assign By'),
                        'componentType' => Field::NAME,
                        'component' => 'MageWorx_OptionTemplates/js/component/assign-type-radio-buttons',
                        'formElement' => RadioSet::NAME,
                        'dataScope' => static::FIELD_ASSIGN_TYPE_NAME,
                        'dataType' => Number::NAME,
                        'sortOrder' => $sortOrder,
                        'options' => $this->getAssignTypes(),
                        'value' => AssignTypeOptions::ASSIGN_BY_GRID
                    ],
                ],
            ],
            'children' => [],
        ];
    }

    /**
     * Select corresponding products using ids
     * Depends on FIELD_ASSIGN_TYPE_NAME
     *
     * @param $sortOrder
     * @return array
     */
    protected function getProductsIdConfig($sortOrder)
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => __('Product IDs'),
                        'componentType' => Field::NAME,
                        'formElement' => Textarea::NAME,
                        'dataScope' => static::FIELD_PRODUCTS_ID_NAME,
                        'dataType' => Text::NAME,
                        'visibleByAssignValue' => AssignTypeOptions::ASSIGN_BY_IDS,
                        'dependsOn' => static::FIELD_ASSIGN_TYPE_NAME,
                        'visible' => false,
                        'sortOrder' => $sortOrder,
                    ],
                ],
            ],
            'children' => [],
        ];
    }

    /**
     * Select corresponding products using skus
     * Depends on FIELD_ASSIGN_TYPE_NAME
     *
     * @param $sortOrder
     * @return array
     */
    protected function getProductsSkuConfig($sortOrder)
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => __('Product SKUs'),
                        'componentType' => Field::NAME,
                        'formElement' => Textarea::NAME,
                        'dataScope' => static::FIELD_PRODUCTS_SKU_NAME,
                        'dataType' => Text::NAME,
                        'visibleByAssignValue' => AssignTypeOptions::ASSIGN_BY_SKUS,
                        'dependsOn' => static::FIELD_ASSIGN_TYPE_NAME,
                        'visible' => false,
                        'sortOrder' => $sortOrder,
                    ],
                ],
            ],
            'children' => [],
        ];
    }

    /**
     * Retrieve filtered by same template type assign options
     *
     * @return array
     */
    protected function getAssignTypes()
    {
        return $this->assignTypeOptions->toOptionArray();
    }
}
