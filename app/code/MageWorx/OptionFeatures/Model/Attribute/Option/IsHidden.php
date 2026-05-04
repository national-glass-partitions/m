<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionFeatures\Model\Attribute\Option;

use MageWorx\OptionFeatures\Helper\Data as Helper;
use MageWorx\OptionBase\Model\Product\Option\AbstractAttribute;

class IsHidden extends AbstractAttribute
{
    const FIELD_MAGE_ONE_OPTIONS_IMPORT = '_custom_option_type';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return Helper::KEY_IS_HIDDEN;
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

        if ($optionData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT] === 'hidden') {
            $preparedOptionData['type']            = 'checkbox';
            $preparedOptionData['is_require']      = 1;
            $preparedOptionData[static::getName()] = 1;
        } else {
            $preparedOptionData[static::getName()] = 0;
        }
    }
}
