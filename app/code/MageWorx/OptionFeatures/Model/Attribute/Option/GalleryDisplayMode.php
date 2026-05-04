<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionFeatures\Model\Attribute\Option;

use MageWorx\OptionFeatures\Helper\Data as Helper;
use MageWorx\OptionBase\Model\Product\Option\AbstractAttribute;

class GalleryDisplayMode extends AbstractAttribute
{
    const FIELD_MAGE_ONE_OPTIONS_IMPORT = '_custom_option_image_mode';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return Helper::KEY_OPTION_GALLERY_DISPLAY_MODE;
    }

    /**
     * {@inheritdoc}
     */
    public function importTemplateMageOne($data)
    {
        if (isset($data['image_mode']) && $data['image_mode'] === '1') {
            return 1;
        }
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
        if (!isset($optionData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT])) {
            return;
        }
        $preparedOptionData[static::getName()] = (int)($optionData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT] === '1');
    }
}
