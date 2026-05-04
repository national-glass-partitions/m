<?php
/**
 * Scommerce Cache Warmer Module UpgradeSchema Class for creating tables in the database
 * @category   Scommerce
 * @package    Scommerce_CacheWarmer
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */
namespace Scommerce\CacheWarmer\Setup;
 
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;
 
/**
 * @codeCoverageIgnore
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function upgrade(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $installer = $setup;
        $installer->startSetup();
        $connection = $installer->getConnection();
        if (version_compare($context->getVersion(), '2.0.5', '<')) {
            $connection->addColumn($installer->getTable('cachewarmer'),
                'reference_id', [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'length'    => 255,
                    'nullable' => false,
                    'comment' => 'REFERENCE ID'
                ]
            );
            $connection->addColumn($installer->getTable('cachewarmer'),'processed_time', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length'    => 255,
                'nullable' => false,
                'comment'   => 'PROCESSED TIME'
            ]);
            $connection->addColumn($installer->getTable('cachewarmer'),'request_path', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length'    => 1056,
                'nullable' => false,
                'comment'   => 'REQUEST PATH'
            ]);
            $connection->addColumn($installer->getTable('cachewarmer'),'store_id', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'length'    => 255,
                'nullable' => false,
                'comment'   => 'STORE ID'
            ]);
            $setup->getConnection()->dropColumn($setup->getTable('cachewarmer'), 'page_url');
            $setup->getConnection()->dropColumn($setup->getTable('cachewarmer'), 'last_cache');
            $setup->getConnection()->dropColumn($setup->getTable('cachewarmer'), 'update_at');

            $connection->addColumn($installer->getTable('cachewarmer'),'page_url', [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length'    => 1000,
                'nullable' => false,
                'comment'   => 'PAGE URL'
            ]);

            $connection->addColumn(
                $setup->getTable('cachewarmer'),
                'updated_at',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                    'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE,
                    'length' => 255,
                    'comment' => 'Updated At'
                ]
            );
        }
        $installer->endSetup();
    }
}