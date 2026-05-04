<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionFeatures\Model\Attribute\Option;

use MageWorx\OptionFeatures\Helper\Data as Helper;
use MageWorx\OptionBase\Model\Product\Option\AbstractAttribute;

class QtyInput extends AbstractAttribute
{
    const FIELD_MAGE_ONE_OPTIONS_IMPORT = '_custom_option_qnty_input';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return Helper::KEY_QTY_INPUT;
    }

    /**
     * {@inheritdoc}
     */
    public function importTemplateMageOne($data)
    {
        return isset($data['qnty_input']) ? $data['qnty_input'] : 0;
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
            return;
        }
        $preparedOptionData[static::getName()] = (int)$optionData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT];
    }
}
