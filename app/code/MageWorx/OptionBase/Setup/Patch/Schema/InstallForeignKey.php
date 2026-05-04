<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionBase\Setup\Patch\Schema;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use \MageWorx\OptionBase\Model\ForeignKeyInstallProcess as ForeignKeyInstallProcess;
use \MageWorx\OptionBase\Model\ProductAttributes;

class InstallForeignKey implements DataPatchInterface
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
            'table_name'            => ProductAttributes::TABLE_NAME,
            'column_name'           => ProductAttributes::COLUMN_PRODUCT_ID,
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
}
