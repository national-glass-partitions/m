<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionSwatches\Model\Attribute\Option;

use MageWorx\OptionSwatches\Helper\Data as Helper;
use MageWorx\OptionBase\Model\Product\Option\AbstractAttribute;

class IsSwatch extends AbstractAttribute
{
    const FIELD_MAGE_ONE_OPTIONS_IMPORT = '_custom_option_type';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return Helper::KEY_IS_SWATCH;
    }


    /**
     * {@inheritdoc}
     */
    public function prepareDataForFrontend($object)
    {
        return [];
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
        if ($optionData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT] === 'swatch') {
            $preparedOptionData['type']            = 'drop_down';
            $preparedOptionData[static::getName()] = 1;
        } elseif ($optionData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT] === 'multiswatch') {
            $preparedOptionData['type']            = 'multiple';
            $preparedOptionData[static::getName()] = 1;
        } else {
            $preparedOptionData[static::getName()] = 0;
        }
    }
}
