<?php
/**
 * Scommerce Cache Warmer Module InstallSchema Class for creating tables in the database
 * @category   Scommerce
 * @package    Scommerce_CacheWarmer
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */
namespace Scommerce\CacheWarmer\Setup;

use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;

class InstallSchema implements InstallSchemaInterface
{
    
    public function install(
            SchemaSetupInterface $setup,
            ModuleContextInterface $context) 
    {
        $setup->startSetup();
        $conn = $setup->getConnection();
        /**
         * Create table 'cachewarmer'
         */
        $tableName = $setup->getTable('cachewarmer');
        if ($conn->isTableExists($tableName) != true) {
            $table = $conn->newTable($tableName)
                    ->addColumn(
                        'entity_id',
                        Table::TYPE_INTEGER, 10, 
                        ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true], 'Entity id '
                    )
                    ->addColumn(
                        'page_type', 
                        Table::TYPE_TEXT,
                        100,
                        ['nullable' => false, 'default' => null],
                        'Page type'
                    )
                    ->addColumn(
                        'page_url', 
                        Table::TYPE_TEXT,
                        100,
                        ['nullable' => false, 'default' => null],
                        'Page url'
                    )
                    ->addColumn(
                        'status',
                        Table::TYPE_SMALLINT,
                        2,
                        ['nullable' => false, 'default' => '1'],
                        'Cache Status'
                    )
                    ->addColumn(
                            'last_cache', 
                            Table::TYPE_TIMESTAMP,
                            null,
                            ['nullable' => true,
                            'default' => Table::TIMESTAMP_INIT],
                            'Last cache'
                    )
                    ->addColumn(
                            'creation_at',
                            Table::TYPE_TIMESTAMP,
                            null,
                            [
                            'nullable' => false,
                            'default' => Table::TIMESTAMP_INIT,
                            ],
                            'Creation Time'
                    )->addColumn(
                            'update_at',
                            Table::TYPE_TIMESTAMP,
                            null,
                            [
                            'nullable' => false,
                            'default' => Table::TIMESTAMP_INIT,
                            ],
                            'Update Time'
                    )->addIndex(
                        $setup->getIdxName('cachewarmer', ['entity_id']),
                        ['entity_id']
                    );
     
            $conn->createTable($table);                        
     
        }
    }

}
