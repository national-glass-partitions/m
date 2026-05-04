<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionVisibility\Model\Attribute\OptionValue;

use MageWorx\OptionVisibility\Helper\Data as Helper;
use MageWorx\OptionBase\Model\Product\Option\AbstractAttribute;

class Disabled extends AbstractAttribute
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return Helper::KEY_DISABLED;
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
        return 0;
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
        // fix for our bug in magento 1 APO for dependent options - issue 814
        if (isset($systemData['usedDependencyIds'][$productData['sku']])) {
            $valueToDisable = !in_array(
                (int)$valueData['_custom_option_row_in_group_id'],
                $systemData['usedDependencyIds'][$productData['sku']]);
        } else {
            $valueToDisable = false;
        }

        if ((int)$optionData['_custom_option_is_dependent'] != 0 && $valueToDisable) {
            $preparedValueData[static::getName()] = true;
        } else {
            $preparedValueData[static::getName()] = false;
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
        return empty($data['custom_option_row_' . $this->getName()])
            ? 0
            : $data['custom_option_row_' . $this->getName()];
    }
}
