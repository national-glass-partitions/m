<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionFeatures\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;


class UpdateCoreConfigData implements DataPatchInterface, PatchVersionInterface
{
    private ModuleDataSetupInterface $moduleDataSetup;

    /**
     * UpdateCoreConfigData constructor.
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $setup      = $this->moduleDataSetup;
        $connection = $setup->getConnection();
        $connection->update(
            $setup->getTable('core_config_data'),
            ['path' => 'mageworx_apo/optionfeatures/use_weight'],
            "path = 'mageworx_optionfeatures/main/use_weight'"
        );
        $connection->update(
            $setup->getTable('core_config_data'),
            ['path' => 'mageworx_apo/optionfeatures/use_absolute_weight'],
            "path = 'mageworx_optionfeatures/main/use_absolute_weight'"
        );
        $connection->update(
            $setup->getTable('core_config_data'),
            ['path' => 'mageworx_apo/optionfeatures/use_cost'],
            "path = 'mageworx_optionfeatures/main/use_cost'"
        );
        $connection->update(
            $setup->getTable('core_config_data'),
            ['path' => 'mageworx_apo/optionfeatures/use_absolute_cost'],
            "path = 'mageworx_optionfeatures/main/use_absolute_cost'"
        );
        $connection->update(
            $setup->getTable('core_config_data'),
            ['path' => 'mageworx_apo/optionfeatures/use_absolute_price'],
            "path = 'mageworx_optionfeatures/main/use_absolute_price'"
        );
        $connection->update(
            $setup->getTable('core_config_data'),
            ['path' => 'mageworx_apo/optionfeatures/use_one_time'],
            "path = 'mageworx_optionfeatures/main/use_one_time'"
        );
        $connection->update(
            $setup->getTable('core_config_data'),
            ['path' => 'mageworx_apo/optionfeatures/use_qty_input'],
            "path = 'mageworx_optionfeatures/main/use_qty_input'"
        );
        $connection->update(
            $setup->getTable('core_config_data'),
            ['path' => 'mageworx_apo/optionfeatures/use_description'],
            "path = 'mageworx_optionfeatures/main/use_description'"
        );
        $connection->update(
            $setup->getTable('core_config_data'),
            ['path' => 'mageworx_apo/optionfeatures/use_option_description'],
            "path = 'mageworx_optionfeatures/main/use_option_description'"
        );
        $connection->update(
            $setup->getTable('core_config_data'),
            ['path' => 'mageworx_apo/optionfeatures/use_is_default'],
            "path = 'mageworx_optionfeatures/main/use_is_default'"
        );
        $connection->update(
            $setup->getTable('core_config_data'),
            ['path' => 'mageworx_apo/optionfeatures/base_image_thumbnail_size'],
            "path = 'mageworx_optionfeatures/main/base_image_thumbnail_size'"
        );
        $connection->update(
            $setup->getTable('core_config_data'),
            ['path' => 'mageworx_apo/optionfeatures/tooltip_image_thumbnail_size'],
            "path = 'mageworx_optionfeatures/main/tooltip_image_thumbnail_size'"
        );
        $connection->update(
            $setup->getTable('core_config_data'),
            ['path' => 'mageworx_apo/optionvisibility/use_is_disabled'],
            "path = 'mageworx_apo/optionfeatures/use_is_disabled'"
        );
        $connection->update(
            $setup->getTable('core_config_data'),
            ['path' => 'mageworx_apo/optionfeatures/base_image_thumbnail_height_size'],
            "path = 'mageworx_apo/optionfeatures/base_image_thumbnail_size'"
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getVersion()
    {
        return '1.0.20';
    }
}
