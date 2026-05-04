<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionSwatches\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;


class UpdateSwatchSize implements DataPatchInterface, PatchVersionInterface
{
    private ModuleDataSetupInterface $moduleDataSetup;

    /**
     * UpdateSwatchSize constructor.
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
        $connection->startSetup();
        $query      = $connection
            ->select()
            ->from($this->moduleDataSetup->getTable('core_config_data'))
            ->columns(['value'])
            ->where("path = 'mageworx_apo/optionfeatures/swatch_size'");
        $swatchSize = $connection->fetchOne($query);
        if (!$swatchSize) {
            return;
        }
        $connection->insert(
            $this->moduleDataSetup->getTable('core_config_data'),
            [
                'scope'    => 'default',
                'scope_id' => 0,
                'path'     => 'mageworx_apo/optionswatches/swatch_height',
                'value'    => $swatchSize,
            ]
        );
        $connection->insert(
            $this->moduleDataSetup->getTable('core_config_data'),
            [
                'scope'    => 'default',
                'scope_id' => 0,
                'path'     => 'mageworx_apo/optionswatches/swatch_width',
                'value'    => $swatchSize,
            ]
        );
        $connection->delete(
            $this->moduleDataSetup->getTable('core_config_data'),
            "path = 'mageworx_apo/optionfeatures/swatch_size'"
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
