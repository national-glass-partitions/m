<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionDependency\Setup\Patch\Schema;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use MageWorx\OptionDependency\Model\Config as DependencyModel;

class MoveOptionIdsDataToNewColumns implements DataPatchInterface, PatchVersionInterface
{
    private SchemaSetupInterface $schemaSetup;

    /**
     * DropTrigger constructor.
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
        $connection = $this->schemaSetup->getConnection();
        // beginTransaction not using here, magento requires the use of declarative schemas to modify tables
        $tableName = $this->schemaSetup->getTable(DependencyModel::TABLE_NAME);
        // If child_option_id isn't exist in table, table has latest schema view (with 'dp' column prefix)
        if ($connection->tableColumnExists($tableName, 'child_option_id')) {
            $this->processFields();
        }
    }

    /**
     * Process fields due to adding declarative schema:
     * Copy old data to new field
     * Get dp_option_id/dp_option_type_id equivalent for option_id/option_type_id
     */
    protected function processFields()
    {
        $tableNames = [
            DependencyModel::TABLE_NAME,
            DependencyModel::OPTIONTEMPLATES_TABLE_NAME
        ];

        foreach ($tableNames as $tableName) {
            $this->moveOldColumnIdsToNewColumns($tableName);
        }
    }

    /**
     * @param string $tableName
     */
    protected function moveOldColumnIdsToNewColumns($tableName)
    {
        $data = [
            DependencyModel::COLUMN_NAME_DP_CHILD_OPTION_ID       => new \Zend_Db_Expr('child_option_id'),
            DependencyModel::COLUMN_NAME_DP_CHILD_OPTION_TYPE_ID  => new \Zend_Db_Expr('child_option_type_id'),
            DependencyModel::COLUMN_NAME_DP_PARENT_OPTION_ID      => new \Zend_Db_Expr('parent_option_id'),
            DependencyModel::COLUMN_NAME_DP_PARENT_OPTION_TYPE_ID => new \Zend_Db_Expr('parent_option_type_id'),
            DependencyModel::COLUMN_NAME_IS_PROCESSED_DP_COLUMNS  => 1
        ];
        $this->schemaSetup->getConnection()->update(
            $this->schemaSetup->getTable($tableName),
            $data,
            [DependencyModel::COLUMN_NAME_IS_PROCESSED_DP_COLUMNS . ' = ?' => '0']
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
        return '2.0.11';
    }
}
