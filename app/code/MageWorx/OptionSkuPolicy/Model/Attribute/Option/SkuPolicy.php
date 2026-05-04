<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionSkuPolicy\Model\Attribute\Option;

use MageWorx\OptionSkuPolicy\Helper\Data as Helper;
use MageWorx\OptionBase\Model\Product\Option\AbstractAttribute;

class SkuPolicy extends AbstractAttribute
{
    const FIELD_MAGE_ONE_OPTIONS_IMPORT = '_custom_option_sku_policy';

    /**
     * @var mixed
     */
    protected $entity;

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return Helper::KEY_SKU_POLICY;
    }

    /**
     * {@inheritdoc}
     */
    public function prepareDataForFrontend($object)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function importTemplateMageOne($data)
    {
        $map = [
            '0' => Helper::SKU_POLICY_USE_CONFIG,
            '1' => Helper::SKU_POLICY_STANDARD,
            '2' => Helper::SKU_POLICY_INDEPENDENT,
            '3' => Helper::SKU_POLICY_GROUPED,
            '4' => Helper::SKU_POLICY_REPLACEMENT,
        ];
        if (!isset($data['sku_policy']) || !isset($map[$data['sku_policy']])) {
            return Helper::SKU_POLICY_USE_CONFIG;
        }
        return $map[$data['sku_policy']];
    }

    /**
     * Prepare data from Magento 1 product csv for future import
     *
     * @param array $systemData
     * @param array $productData
     * @param array $optionData
     * @param array $preparedOptionData
     * @param array $valueData
     * @param array $preparedValueData
     * @return void
     */
    public function prepareOptionsMageOne($systemData, $productData, $optionData, &$preparedOptionData, $valueData = [], &$preparedValueData = [])
    {
        if (!isset($optionData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT])) {
            $preparedOptionData[static::getName()] = Helper::SKU_POLICY_USE_CONFIG;
            return;
        }
        $map = [
            '0' => Helper::SKU_POLICY_USE_CONFIG,
            '1' => Helper::SKU_POLICY_STANDARD,
            '2' => Helper::SKU_POLICY_INDEPENDENT,
            '3' => Helper::SKU_POLICY_GROUPED,
            '4' => Helper::SKU_POLICY_REPLACEMENT,
        ];
        $preparedOptionData[static::getName()] = $map[$optionData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT]];
    }
}
