<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionFeatures\Model\Attribute\Option;

use MageWorx\OptionFeatures\Helper\Data as Helper;
use MageWorx\OptionBase\Model\Product\Option\AbstractAttribute;

class SelectionLimitTo extends AbstractAttribute
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return Helper::KEY_SELECTION_LIMIT_TO;
    }

    /**
     * Prepare attribute data before save
     * Returns modified value, which is ready for db save
     *
     * @return mixed|string
     */
    public function prepareDataBeforeSave($data)
    {
        if (is_object($data)) {
            $fromValue = $data->getData(Helper::KEY_SELECTION_LIMIT_FROM);
            $toValue   = $data->getData($this->getName());
            if ($toValue > 0 && $fromValue > $toValue) {
                return $fromValue;
            }
            return $toValue;
        } elseif (is_array($data)
            && isset($data[$this->getName()])
            && isset($data[Helper::KEY_SELECTION_LIMIT_FROM])
        ) {
            $fromValue = $data[Helper::KEY_SELECTION_LIMIT_FROM];
            $toValue   = $data[$this->getName()];
            if ($toValue > 0 && $fromValue > $toValue) {
                return (string)$fromValue;
            }
            return $toValue;
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function importTemplateMageOne($data)
    {
        return isset($data[$this->getName()]) ? $data[$this->getName()] : '';
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
        $preparedOptionData[static::getName()] = 0;
    }
}
