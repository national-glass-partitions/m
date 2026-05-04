<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionDependency\Setup\Patch\Schema;


use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use MageWorx\OptionDependency\Model\Config as DependencyModel;

class InstallOldColumns implements DataPatchInterface, PatchVersionInterface
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
    public function apply(): void
    {
        $connection = $this->schemaSetup->getConnection();
        $tableName  = $this->schemaSetup->getTable(DependencyModel::TABLE_NAME);

        /* If child_option_id isn't exist in table, table has latest schema view (with 'dp' column prefix)
           If is_processed column isn't exist and child_option_id is exist - APO version is very old,
           need to install old columns for correct move data
         */
        if ($connection->tableColumnExists($tableName, 'child_option_id') &&
            !$connection->tableColumnExists($tableName, 'is_processed')
        ) {
            $this->processFields();
        }
    }

    /**
     * Process fields due to adding declarative schema:
     * Restore old columns
     */
    protected function processFields(): void
    {
        $tableNames = [
            DependencyModel::TABLE_NAME,
            DependencyModel::OPTIONTEMPLATES_TABLE_NAME
        ];

        foreach ($tableNames as $tableName) {
            $this->updateTableSchema($tableName);
        }
    }

    protected function updateTableSchema(string $tableName): void
    {
        $connection = $this->schemaSetup->getConnection();
        $data       = $this->getData();
        $tableName  = $this->schemaSetup->getTable($tableName);
        foreach ($data as $item) {
            if ($connection->isTableExists($tableName)) {
                if ($this->isTableColumnMissed($tableName, $item)) {
                    $connection->addColumn(
                        $tableName,
                        $item['field_name'],
                        $item['params']
                    );
                }
            }
        }
    }

    /**
     * Retrieve module fields data array
     *
     * @return array
     */
    public function getData(): array
    {
        $dataArray = [
            [
                'field_name' => 'child_mageworx_option_id',
                'params'     => [
                    'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length'   => 255,
                    'nullable' => false,
                    'default'  => '',
                    'comment'  => 'Child MageWorx Option Id',
                ]
            ],
            [
                'field_name' => 'child_mageworx_option_type_id',
                'params'     => [
                    'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length'   => 255,
                    'nullable' => false,
                    'default'  => '',
                    'comment'  => 'Child MageWorx Option Type Id',
                ]
            ],
            [
                'field_name' => 'parent_mageworx_option_id',
                'params'     => [
                    'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length'   => 255,
                    'nullable' => false,
                    'default'  => '',
                    'comment'  => 'Parent MageWorx Option Id',
                ]
            ],
            [
                'field_name' => 'parent_mageworx_option_type_id',
                'params'     => [
                    'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length'   => 255,
                    'nullable' => false,
                    'default'  => '',
                    'comment'  => 'Parent MageWorx Option Type Id',
                ]
            ],
            [
                'field_name' => 'is_processed',
                'params'     => [
                    'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    'unsigned' => true,
                    'nullable' => false,
                    'default'  => '0',
                    'comment'  => 'Is Processed',
                ]
            ]
        ];

        return $dataArray;
    }

    /**
     * Check if table column must be added
     *
     * {@inheritdoc}
     */
    private function isTableColumnMissed(string $tableName, array $item): bool
    {
        return !$this->schemaSetup->getConnection()->tableColumnExists($tableName, $item['field_name']);
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
        return '2.0.11';
    }
}
