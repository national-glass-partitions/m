<?php


namespace MageWorx\OptionDependency\Setup\Patch\Schema;


use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use MageWorx\OptionDependency\Model\Config as DependencyModel;

class RemoveUnusedIdColumns implements DataPatchInterface
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
        $tableNames = [
            DependencyModel::TABLE_NAME,
            DependencyModel::OPTIONTEMPLATES_TABLE_NAME
        ];

        foreach ($tableNames as $tableName) {
            $columnsData = [
                'child_option_id',
                'child_option_type_id',
                'parent_option_id',
                'parent_option_type_id',
                'child_mageworx_option_id',
                'child_mageworx_option_type_id',
                'parent_mageworx_option_id',
                'parent_mageworx_option_type_id',
                'is_processed'
            ];
            foreach ($columnsData as $columnName) {
                $table = $this->schemaSetup->getTable($tableName);
                if ($connection->tableColumnExists($table, $columnName)) {
                    $connection->dropColumn($table, $columnName);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [
            \MageWorx\OptionDependency\Setup\Patch\Schema\MoveOptionIdsDataToNewColumns::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
