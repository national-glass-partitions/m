<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionInventory\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\Stdlib\ArrayManager;

class Inventory extends AbstractModifier implements \MageWorx\OptionBase\Ui\DataProvider\Product\Form\Modifier\ModifierInterface
{
    const FIELD_MANAGE_STOCK_NAME = 'manage_stock';
    const FIELD_QUANTITY_NAME     = 'qty';

    protected ArrayManager $arrayManager;
    protected \MageWorx\OptionInventory\Helper\Data $helperData;
    protected array $meta = [];

    /**
     * @param ArrayManager $arrayManager
     */
    public function __construct(
        ArrayManager $arrayManager,
        \MageWorx\OptionInventory\Helper\Data $helperData
    ) {
        $this->arrayManager = $arrayManager;
        $this->helperData   = $helperData;
    }

    /**
     * Get sort order of modifier to load modifiers in the right order
     *
     * @return int
     */
    public function getSortOrder()
    {
        return 30;
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

        $this->addInventoryFields();

        return $this->meta;
    }

    protected function addInventoryFields()
    {
        $groupCustomOptionsName =
            \Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\CustomOptions::GROUP_CUSTOM_OPTIONS_NAME;
        $inventoryFields        = $this->getInventoryFields();

        $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
        ['container_option']['children']['values']['children']['record']['children'] = array_replace_recursive(
            $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
            ['container_option']['children']['values']['children']['record']['children'],
            $inventoryFields
        );
    }

    /**
     * Create additional custom options fields
     *
     * @return array
     */
    protected function getInventoryFields()
    {
        $fields = [
            'qty'          => [
                'arguments' => [
                    'data' => [
                        'config'  => [
                            'label'         => __('Quantity'),
                            'componentType' => \Magento\Ui\Component\Form\Field::NAME,
                            'formElement'   => \Magento\Ui\Component\Form\Element\Input::NAME,
                            'dataScope'     => static::FIELD_QUANTITY_NAME,
                            'dataType'      => \Magento\Ui\Component\Form\Element\DataType\Number::NAME,
                            'fit'           => true,
                            'validation'    => [
                                'validate-number' => true,
                            ],
                            'sortOrder'     => 100,
                            'visible'       => $this->helperData->isEnabledOptionInventory()
                        ],
                        'imports' => [
                            'seeminglyArbitraryValue' => '${ $.provider }:data.form_id_field_name',
                        ],
                        'exports' => [
                            'seeminglyArbitraryValue' => '${ $.externalProvider }:params.form_id_field_name',
                        ],
                    ],
                ],
            ],
            'manage_stock' => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'label' => __('Manage Stock'),

                            'componentType' => \Magento\Ui\Component\Form\Field::NAME,
                            'formElement'   => \Magento\Ui\Component\Form\Element\Checkbox::NAME,
                            'dataScope'     => static::FIELD_MANAGE_STOCK_NAME,
                            'dataType'      => \Magento\Ui\Component\Form\Element\DataType\Number::NAME,
                            'prefer'        => 'toggle',
                            'valueMap'      => [
                                'true'  => \MageWorx\OptionInventory\Helper\Stock::MANAGE_STOCK_ENABLED,
                                'false' => \MageWorx\OptionInventory\Helper\Stock::MANAGE_STOCK_DISABLED,
                            ],
                            'sortOrder'     => 110,
                            'visible'       => $this->helperData->isEnabledOptionInventory()
                        ],
                    ],
                ],
            ],
        ];

        return $fields;
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
