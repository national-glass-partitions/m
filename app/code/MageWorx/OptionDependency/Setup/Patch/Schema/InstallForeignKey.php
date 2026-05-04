<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionDependency\Setup\Patch\Schema;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use \MageWorx\OptionBase\Model\ForeignKeyInstallProcess as ForeignKeyInstallProcess;

class InstallForeignKey implements DataPatchInterface, PatchVersionInterface
{
    protected ForeignKeyInstallProcess $foreignKeyInstallProcess;

    /**
     * InstallForeignKey constructor.
     *
     * @param ForeignKeyInstallProcess $foreignKeyInstallProcess
     */
    public function __construct(
        ForeignKeyInstallProcess $foreignKeyInstallProcess
    ) {
        $this->foreignKeyInstallProcess = $foreignKeyInstallProcess;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(): void
    {
        $foreignKey = [
            'table_name'            => 'mageworx_option_dependency',
            'column_name'           => 'product_id',
            'reference_table_name'  => 'catalog_product_entity',
            'on_delete'             => Table::ACTION_CASCADE
        ];
        $this->foreignKeyInstallProcess->installProcess($foreignKey);
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getVersion(): string
    {
        return '2.0.4';
    }
}
