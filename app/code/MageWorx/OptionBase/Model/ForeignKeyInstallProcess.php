<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionBase\Model;


use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\SchemaSetupInterface;
use \MageWorx\OptionBase\Helper\Data as HelperBase;

class ForeignKeyInstallProcess
{
    private SchemaSetupInterface $schemaSetup;
    private HelperBase $helperBase;
    protected ResourceConnection $resource;

    /**
     * ForeignKeyInstallProcess constructor.
     *
     * @param SchemaSetupInterface $schemaSetup
     * @param HelperBase $helperBase
     * @param ResourceConnection $resource
     */
    public function __construct(
        SchemaSetupInterface $schemaSetup,
        HelperBase $helperBase,
        ResourceConnection $resource
    ) {
        $this->schemaSetup = $schemaSetup;
        $this->helperBase  = $helperBase;
        $this->resource    = $resource;
    }

    public function installProcess($foreignKeys): void
    {
        $installer           = $this->resource;
        $connection          = $this->schemaSetup->getConnection();
        $referenceColumnName = $this->helperBase->isEnterprise() ? 'row_id' : 'entity_id';

        // install to Magento
        $tableName = $installer->getTableName($foreignKeys['table_name']);
        if ($connection->isTableExists($tableName) &&
            !$this->helperBase->isForeignKeyExist($foreignKeys, $tableName, $referenceColumnName)
        ) {
            $referenceTableName = $installer->getTableName($foreignKeys['reference_table_name']);

            $fkk = $installer->getFkName(
                $tableName,
                $foreignKeys['column_name'],
                $referenceTableName,
                $referenceColumnName
            );
            $connection->addForeignKey(
                $fkk,
                $tableName,
                $foreignKeys['column_name'],
                $referenceTableName,
                $referenceColumnName,
                $foreignKeys['on_delete']
            );
        }
    }
}
