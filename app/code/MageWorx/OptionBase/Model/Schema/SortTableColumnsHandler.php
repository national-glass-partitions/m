<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionBase\Model\Schema;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\Declaration\Schema\Declaration\ReaderComposite;

class SortTableColumnsHandler
{
    private ReaderComposite $readerComposite;
    protected ResourceConnection $resource;
    protected array $columnsToUnSet;

    /**
     * SortTableColumns constructor.
     *
     * @param ReaderComposite $readerComposite
     * @param ResourceConnection $resource
     */
    public function __construct(
        ReaderComposite $readerComposite,
        ResourceConnection $resource
    ) {
        $this->readerComposite = $readerComposite;
        $this->resource        = $resource;
    }

    public function sortTableColumnProcess(string $moduleName, array $tableData): void
    {
        $data       = $this->readerComposite->read($moduleName);
        $installer  = $this->resource;
        $connection = $installer->getConnection();

        foreach ($tableData as $tableName) {
            $this->columnsToUnSet = [];
            $dbTableName          = $installer->getTableName($tableName);

            if ($connection->isTableExists($dbTableName)) {
                $originalColumnsState = $data['table'][$tableName]['column'];

                foreach ($originalColumnsState as $columnName => $columnSchema) {
                    if (isset($columnSchema['disabled']) && $columnSchema['disabled']) {
                        unset($originalColumnsState[$columnName]);
                    }
                }

                $originalColumnsStateMap     = array_keys($originalColumnsState);
                $originalColumnsStateReverse = array_reverse($originalColumnsStateMap, true);
                $currentColumnsState         = $this->describeTableColumnProcess(
                    $dbTableName,
                    $originalColumnsState,
                    $connection
                );

                foreach ($currentColumnsState as $columnName => $columnSchema) {
                    if (!in_array($columnName, $originalColumnsStateMap)) {
                        $this->columnsToUnSet[] = $columnName;
                    }
                }

                if ($this->schemaValidation($currentColumnsState, $originalColumnsStateMap)) {
                    continue;
                }

                for ($i = count($originalColumnsStateReverse) - 1; $i > 0; $i--) {
                    $columnName          = $originalColumnsStateReverse[$i];
                    $columnToMove        = $columnName;
                    $definition          = $currentColumnsState[$columnName];
                    $definition['AFTER'] = $originalColumnsStateReverse[$i - 1];
                    $connection->modifyColumnByDdl($dbTableName, $columnToMove, $definition);

                    if ($i == 1) {
                        $currentColumnsState = $this->describeTableColumnProcess(
                            $dbTableName,
                            $originalColumnsState,
                            $connection
                        );
                        if ($this->schemaValidation($currentColumnsState, $originalColumnsStateMap)) {
                            continue;
                        }
                        $i = count($originalColumnsState);
                    }
                }
            }
        }
    }

    private function describeTableColumnProcess(
        string $tableName,
        array $originalColumnsState,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection
    ): array {
        $tableColumnsDescribeData = $connection->describeTable($tableName);

        foreach ($tableColumnsDescribeData as $columnName => &$columnSchema) {
            if (isset($originalColumnsState[$columnName]['comment'])) {
                $columnSchema['COMMENT'] = $originalColumnsState[$columnName]['comment'];
            }
            if (isset($originalColumnsState[$columnName]['length'])) {
                $columnSchema['LENGTH'] = $originalColumnsState[$columnName]['length'];
            }
        }

        return $tableColumnsDescribeData;
    }

    private function schemaValidation(array $currentColumnsState, array $originalColumnsStateMap): bool
    {
        foreach ($this->columnsToUnSet as $column) {
            if (isset($currentColumnsState[$column])) {
                unset($currentColumnsState[$column]);
            }
        }

        return array_keys($currentColumnsState) === $originalColumnsStateMap;
    }
}
