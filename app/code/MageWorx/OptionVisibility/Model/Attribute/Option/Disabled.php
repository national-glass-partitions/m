<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionVisibility\Model\Attribute\Option;

use MageWorx\OptionVisibility\Helper\Data as Helper;
use MageWorx\OptionBase\Model\Product\Option\AbstractAttribute;

class Disabled extends AbstractAttribute
{
    const FIELD_MAGE_ONE_OPTIONS_IMPORT = '_custom_option_view_mode';

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
        if (isset($data['is_disabled']) && $data['is_disabled'] === 1) {
            return 1;
        }
        return isset($data['view_mode']) && $data['view_mode'] === '0' ? 1 : 0;
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
        if (!empty($preparedOptionData[static::getName()])) {
            $preparedOptionData[static::getName()] = true;
            return;
        }

        if (isset($systemData['usedDependencyIds'][$productData['sku']])) {
            $optionToDisable = !in_array(
                (int)$optionData['_custom_option_in_group_id'],
                $systemData['usedDependencyIds'][$productData['sku']]);
        } else {
            $optionToDisable = false;
        }

        // fix for our bug in magento 1 APO for dependent options - issue 814
        if (!$this->baseHelper->isSelectableOption($preparedOptionData['type'])
            && (int)$optionData['_custom_option_is_dependent'] != 0
            && $optionToDisable
        ) {
            $preparedOptionData[static::getName()] = true;
            return;
        }

        $preparedOptionData[static::getName()] = false;
        if (!isset($optionData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT])
            || !is_array($optionData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT])
        ) {
            return;
        }

        foreach ($optionData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT] as $datumStore => $datumValue) {
            if ($datumStore == '0' && $datumValue !== '0') {
                continue;
            }
            $preparedOptionData[static::getName()] = true;
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
        return empty($data['custom_option_' . $this->getName()]) ? 0 : $data['custom_option_' . $this->getName()];
    }
}
