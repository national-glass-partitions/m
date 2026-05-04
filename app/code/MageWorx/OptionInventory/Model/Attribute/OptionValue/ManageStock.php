<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionInventory\Model\Attribute\OptionValue;

use MageWorx\OptionInventory\Helper\Data as Helper;
use MageWorx\OptionBase\Model\Product\Option\AbstractAttribute;

class ManageStock extends AbstractAttribute
{
    const FIELD_MAGE_ONE_OPTIONS_IMPORT = '_custom_option_row_customoptions_qty';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return Helper::KEY_MANAGE_STOCK;
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
        if (!isset($valueData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT])) {
            $preparedValueData[static::getName()] = 0;
            return;
        }
        $preparedValueData[static::getName()] = (int)($valueData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT] !== '');
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
