<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionFeatures\Model\Attribute\Option;

use MageWorx\OptionFeatures\Helper\Data as Helper;
use MageWorx\OptionBase\Model\Product\Option\AbstractAttribute;

class DivClass extends AbstractAttribute
{
    const FIELD_MAGE_ONE_OPTIONS_IMPORT = '_custom_option_div_class';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return Helper::KEY_DIV_CLASS;
    }

    /**
     * {@inheritdoc}
     */
    public function importTemplateMageOne($data)
    {
        return isset($data['div_class']) ? $data['div_class'] : '';
    }

    /**
     * {@inheritdoc}
     */
    public function importTemplateMageTwo($data)
    {
        return isset($data[$this->getName()]) ? $data[$this->getName()] : '';
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
        if (isset($optionData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT])) {
            $preparedOptionData[static::getName()] = $optionData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT];
        } else {
            $preparedOptionData[static::getName()] = '';
        }
    }

    /**
     * Prepare data for attributes, which do NOT have own database tables, for Magento2 product import
     *
     * @param array $data
     * @param string $type
     * @return mixed
     */
    public function prepareImportDataMageTwo($data, $type)
    {
        return empty($data['custom_option_' . $this->getName()]) ? '' : $data['custom_option_' . $this->getName()];
    }
}
