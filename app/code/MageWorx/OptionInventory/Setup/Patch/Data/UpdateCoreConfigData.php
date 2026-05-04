<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionInventory\Setup\Patch\Data;

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
        $connection = $this->moduleDataSetup->getConnection();
        $connection->update(
            $this->moduleDataSetup->getTable('core_config_data'),
            ['path' => 'mageworx_apo/optioninventory/display_option_inventory_on_frontend'],
            "path = 'mageworx_optioninventory/optioninventory_main/display_option_inventory_on_frontend'"
        );
        $connection->update(
            $this->moduleDataSetup->getTable('core_config_data'),
            ['path' => 'mageworx_apo/optioninventory/disable_out_of_stock_options'],
            "path = 'mageworx_optioninventory/optioninventory_main/disable_out_of_stock_options'"
        );
        $connection->update(
            $this->moduleDataSetup->getTable('core_config_data'),
            ['path' => 'mageworx_apo/optioninventory/display_out_of_stock_message'],
            "path = 'mageworx_optioninventory/optioninventory_main/display_out_of_stock_message'"
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
        return '2.0.1';
    }
}
