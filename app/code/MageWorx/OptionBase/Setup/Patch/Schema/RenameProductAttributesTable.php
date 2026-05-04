<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionBase\Setup\Patch\Schema;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use MageWorx\OptionBase\Model\ProductAttributes;

class RenameProductAttributesTable implements DataPatchInterface, PatchVersionInterface
{
    private SchemaSetupInterface $schemaSetup;

    /**
     * RenameProductAttributesTable constructor.
     *
     * @param SchemaSetupInterface $schemaSetup
     */
    public function __construct(
        SchemaSetupInterface $schemaSetup
    ) {
        $this->schemaSetup = $schemaSetup;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->schemaSetup->startSetup();
        $setup        = $this->schemaSetup;
        $connection   = $setup->getConnection();
        $tableName    = $setup->getTable(ProductAttributes::TABLE_NAME);
        $oldTableName = $setup->getTable('mageworx_optionfeatures_product_attributes');

        if ($connection->isTableExists($oldTableName) && !$connection->isTableExists($tableName) ) {
            $connection    = $setup->getConnection();
            $select        = $connection->select()->from($oldTableName);
            $oldTableData  = $connection->fetchAssoc($select);
            $connection->insertMultiple($tableName, $oldTableData);
            $connection->dropTable($oldTableName);
        }

        $this->schemaSetup->endSetup();
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
        return '2.0.5';
    }
}
