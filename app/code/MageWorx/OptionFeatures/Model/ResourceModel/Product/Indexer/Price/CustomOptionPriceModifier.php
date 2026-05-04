<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See https://www.mageworx.com/terms-and-conditions for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionFeatures\Model\ResourceModel\Product\Indexer\Price;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Sql\ColumnValueExpression;
use Magento\Catalog\Model\ResourceModel\Product\Indexer\Price\PriceModifierInterface;
use Magento\Catalog\Model\ResourceModel\Product\Indexer\Price\IndexTableStructure;
use Magento\Framework\DB\Adapter\AdapterInterface;

/**
 * Class for modify custom option price.
 * Used instead of \Magento\Catalog\Model\ResourceModel\Product\Indexer\Price\CustomOptionPriceModifier
 */
class CustomOptionPriceModifier implements PriceModifierInterface
{
    protected \Magento\Framework\App\ResourceConnection $resource;
    protected \Magento\Framework\EntityManager\MetadataPool $metadataPool;
    protected \Magento\Framework\DB\Sql\ColumnValueExpressionFactory $columnValueExpressionFactory;
    protected \Magento\Catalog\Helper\Data $dataHelper;
    protected \Magento\Framework\Indexer\Table\StrategyInterface $tableStrategy;
    protected string $connectionName;
    protected bool $isPriceGlobalFlag;
    protected AdapterInterface $connection;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\EntityManager\MetadataPool $metadataPool,
        \Magento\Framework\DB\Sql\ColumnValueExpressionFactory $columnValueExpressionFactory,
        \Magento\Catalog\Helper\Data $dataHelper,
        \Magento\Framework\Indexer\Table\StrategyInterface $tableStrategy,
        string $connectionName = 'indexer'
    ) {
        $this->resource                     = $resource;
        $this->metadataPool                 = $metadataPool;
        $this->columnValueExpressionFactory = $columnValueExpressionFactory;
        $this->dataHelper                   = $dataHelper;
        $this->tableStrategy                = $tableStrategy;
        $this->connectionName               = $connectionName;
    }

    /**
     * Apply custom option price to temporary index price table
     *
     * @param IndexTableStructure $priceTable
     * @param array $entityIds
     * @return void
     * @throws \Exception
     */
    public function modifyPrice(IndexTableStructure $priceTable, array $entityIds = []): void
    {
        // no need to run all queries if current products have no custom options
        if (!$this->checkIfCustomOptionsExist($priceTable)) {
            return;
        }

        $coaTable = $this->getCustomOptionAggregateTable();
        $this->prepareCustomOptionAggregateTable();

        $copTable = $this->getCustomOptionPriceTable();
        $this->prepareCustomOptionPriceTable();

        $connection      = $this->getConnection();
        $finalPriceTable = $priceTable->getTableName();

        $select = $this->getSelectForOptionsWithMultipleValues($finalPriceTable);
        $connection->query($select->insertFromSelect($coaTable));

        $select = $this->getSelectForOptionsWithOneValue($finalPriceTable);
        $connection->query($select->insertFromSelect($coaTable));

        $select = $this->getSelectAggregated($coaTable);
        $connection->query($select->insertFromSelect($copTable));

        // update tmp price index with prices from custom options (from previous aggregated table)
        $select = $this->getSelectForUpdate($copTable);
        $connection->query($select->crossUpdateFromSelect(['i' => $finalPriceTable]));

        $connection->delete($coaTable);
        $connection->delete($copTable);
    }

    /**
     * Check if custom options exist.
     *
     * @param IndexTableStructure $priceTable
     * @return bool
     * @throws \Exception
     */
    protected function checkIfCustomOptionsExist(IndexTableStructure $priceTable): bool
    {
        $metadata = $this->metadataPool->getMetadata(ProductInterface::class);
        $select   = $this->getConnection()->select();
        $select
            ->from(['i' => $priceTable->getTableName()], ['entity_id'])
            ->join(
                ['e' => $this->getTable('catalog_product_entity')],
                'e.entity_id = i.entity_id',
                []
            )->join(
                ['o' => $this->getTable('catalog_product_option')],
                'o.product_id = e.' . $metadata->getLinkField(),
                ['option_id']
            );

        return !empty($this->getConnection()->fetchRow($select));
    }

    /**
     * Get connection.
     *
     * @return AdapterInterface
     */
    protected function getConnection(): AdapterInterface
    {
        if (!isset($this->connection)) {
            $this->connection = $this->resource->getConnection($this->connectionName);
        }

        return $this->connection;
    }

    /**
     * Prepare prices for products with custom options that has multiple values
     *
     * @param string $sourceTable
     * @return Select
     * @throws \Exception
     */
    protected function getSelectForOptionsWithMultipleValues(string $sourceTable): Select
    {
        $connection = $this->resource->getConnection($this->connectionName);
        $metadata   = $this->metadataPool->getMetadata(ProductInterface::class);
        $select     = $connection->select();
        $select
            ->from(['i' => $sourceTable], ['entity_id', 'customer_group_id', 'website_id'])
            ->join(
                ['e' => $this->getTable('catalog_product_entity')],
                'e.entity_id = i.entity_id',
                []
            )->join(
                ['cwd' => $this->getTable('catalog_product_index_website')],
                'i.website_id = cwd.website_id',
                []
            )->join(
                ['o' => $this->getTable('catalog_product_option')],
                'o.product_id = e.' . $metadata->getLinkField(),
                ['option_id']
            )->join(
                ['ot' => $this->getTable('catalog_product_option_type_value')],
                'ot.option_id = o.option_id',
                []
            )->join(
                ['otpd' => $this->getTable('catalog_product_option_type_price')],
                'otpd.option_type_id = ot.option_type_id AND otpd.store_id = 0',
                []
            )->group(['i.entity_id', 'i.customer_group_id', 'i.website_id', 'o.option_id']);

        if ($this->isPriceGlobal()) {
            $optPriceValue = 'otpd.price';
            $optPriceType  = 'otpd.price_type';
        } else {
            $select->joinLeft(
                ['otps' => $this->getTable('catalog_product_option_type_price')],
                'otps.option_type_id = otpd.option_type_id AND otps.store_id = cwd.default_store_id',
                []
            );
            $optPriceValue = $connection->getCheckSql('otps.option_type_price_id > 0', 'otps.price', 'otpd.price');
            $optPriceType  = $connection->getCheckSql(
                'otps.option_type_price_id > 0',
                'otps.price_type',
                'otpd.price_type'
            );
        }
        $optPriceTypeCondition = "{$optPriceType} = 'fixed'";

        $minPriceRound    = $this->columnValueExpressionFactory
            ->create([
                         'expression' => "ROUND(i.final_price * ({$optPriceValue} / 100), 4)"
                     ]);
        $minPriceExpr     = $connection->getCheckSql($optPriceTypeCondition, $optPriceValue, $minPriceRound);
        $minPriceMin      = $this->columnValueExpressionFactory
            ->create([
                         'expression' => "MIN({$minPriceExpr})"
                     ]);
        $originalMinPrice = $connection->getCheckSql("MIN(o.is_require) = 1", $minPriceMin, '0');
        $minPrice         = $connection->getCheckSql(
            "(MIN(o.type) = 'checkbox' AND MIN(o.is_hidden) = '1')",
            "SUM({$minPriceExpr})",
            $originalMinPrice
        );

        $maxPriceRound = $this->columnValueExpressionFactory
            ->create([
                         'expression' => "ROUND(i.final_price * ({$optPriceValue} / 100), 4)"
                     ]);
        $maxPriceExpr  = $connection->getCheckSql($optPriceTypeCondition, $optPriceValue, $maxPriceRound);
        $maxPrice      = $connection->getCheckSql(
            "(MIN(o.type)='radio' OR MIN(o.type)='drop_down')",
            "MAX({$maxPriceExpr})",
            "SUM({$maxPriceExpr})"
        );

        $tierPriceRound = $this->columnValueExpressionFactory
            ->create([
                         'expression' => "ROUND(i.tier_price * ({$optPriceValue} / 100), 4)"
                     ]);
        $tierPriceExpr  = $connection->getCheckSql($optPriceTypeCondition, $optPriceValue, $tierPriceRound);
        $tierPriceMin   = $this->columnValueExpressionFactory
            ->create([
                         'expression' => "MIN({$tierPriceExpr})"
                     ]);
        $tierPriceValue = $connection->getCheckSql("MIN(o.is_require) > 0", $tierPriceMin, 0);
        $tierPrice      = $connection->getCheckSql("MIN(i.tier_price) IS NOT NULL", $tierPriceValue, "NULL");

        $select->columns(
            [
                'min_price'  => $minPrice,
                'max_price'  => $maxPrice,
                'tier_price' => $tierPrice,
            ]
        );

        return $select;
    }

    /**
     * Prepare prices for products with custom options that has single value
     *
     * @param string $sourceTable
     * @return Select
     * @throws \Exception
     */
    protected function getSelectForOptionsWithOneValue(string $sourceTable): Select
    {
        $connection = $this->resource->getConnection($this->connectionName);
        $metadata   = $this->metadataPool->getMetadata(ProductInterface::class);

        $select = $connection->select();
        $select
            ->from(['i' => $sourceTable], ['entity_id', 'customer_group_id', 'website_id'])
            ->join(
                ['e' => $this->getTable('catalog_product_entity')],
                'e.entity_id = i.entity_id',
                []
            )->join(
                ['cwd' => $this->getTable('catalog_product_index_website')],
                'i.website_id = cwd.website_id',
                []
            )->join(
                ['o' => $this->getTable('catalog_product_option')],
                'o.product_id = e.' . $metadata->getLinkField(),
                ['option_id']
            )->join(
                ['opd' => $this->getTable('catalog_product_option_price')],
                'opd.option_id = o.option_id AND opd.store_id = 0',
                []
            );

        if ($this->isPriceGlobal()) {
            $optPriceValue = 'opd.price';
            $optPriceType  = 'opd.price_type';
        } else {
            $select->joinLeft(
                ['ops' => $this->getTable('catalog_product_option_price')],
                'ops.option_id = opd.option_id AND ops.store_id = cwd.default_store_id',
                []
            );

            $optPriceValue = $connection->getCheckSql('ops.option_price_id > 0', 'ops.price', 'opd.price');
            $optPriceType  = $connection->getCheckSql('ops.option_price_id > 0', 'ops.price_type', 'opd.price_type');
        }
        $optPriceTypeCondition = "{$optPriceType} = 'fixed'";

        $minPriceRound = $this->columnValueExpressionFactory
            ->create([
                         'expression' => "ROUND(i.final_price * ({$optPriceValue} / 100), 4)"
                     ]);
        $priceExpr     = $connection->getCheckSql($optPriceTypeCondition, $optPriceValue, $minPriceRound);
        $minPrice      = $connection->getCheckSql("{$priceExpr} > 0 AND o.is_require = 1", $priceExpr, 0);

        $maxPrice = $priceExpr;

        $tierPriceRound = $this->columnValueExpressionFactory
            ->create([
                         'expression' => "ROUND(i.tier_price * ({$optPriceValue} / 100), 4)"
                     ]);
        $tierPriceExpr  = $connection->getCheckSql($optPriceTypeCondition, $optPriceValue, $tierPriceRound);
        $tierPriceValue = $connection->getCheckSql("{$tierPriceExpr} > 0 AND o.is_require = 1", $tierPriceExpr, 0);
        $tierPrice      = $connection->getCheckSql("i.tier_price IS NOT NULL", $tierPriceValue, "NULL");

        $select->columns(
            [
                'min_price'  => $minPrice,
                'max_price'  => $maxPrice,
                'tier_price' => $tierPrice,
            ]
        );

        return $select;
    }

    /**
     * Aggregate prices with one and multiply options into one table
     *
     * @param string $sourceTable
     * @return \Magento\Framework\DB\Select
     */
    protected function getSelectAggregated(string $sourceTable): Select
    {
        $connection = $this->resource->getConnection($this->connectionName);
        $select     = $connection->select();
        $select
            ->from(
                [$sourceTable],
                [
                    'entity_id',
                    'customer_group_id',
                    'website_id',
                    'min_price'  => 'SUM(min_price)',
                    'max_price'  => 'SUM(max_price)',
                    'tier_price' => 'SUM(tier_price)',
                ]
            )->group(
                ['entity_id', 'customer_group_id', 'website_id']
            );

        return $select;
    }

    /**
     * Get select for update.
     *
     * @param string $sourceTable
     * @return \Magento\Framework\DB\Select
     */
    protected function getSelectForUpdate(string $sourceTable): Select
    {
        $connection = $this->resource->getConnection($this->connectionName);
        $select     = $connection->select();
        $select
            ->join(
                ['io' => $sourceTable],
                'i.entity_id = io.entity_id AND i.customer_group_id = io.customer_group_id' .
                ' AND i.website_id = io.website_id',
                []
            )->columns(
                [
                    'min_price'  => new ColumnValueExpression('i.min_price + io.min_price'),
                    'max_price'  => new ColumnValueExpression('i.max_price + io.max_price'),
                    'tier_price' => $connection->getCheckSql(
                        'i.tier_price IS NOT NULL',
                        'i.tier_price + io.tier_price',
                        'NULL'
                    ),
                ]
            );

        return $select;
    }

    /**
     * Get table name.
     *
     * @param string $tableName
     * @return string
     */
    protected function getTable(string $tableName): string
    {
        return $this->resource->getTableName($tableName, $this->connectionName);
    }

    /**
     * Is price scope global.
     *
     * @return bool
     */
    protected function isPriceGlobal(): bool
    {
        if (!isset($this->isPriceGlobalFlag)) {
            $this->isPriceGlobalFlag = $this->dataHelper->isPriceGlobal();
        }

        return $this->isPriceGlobalFlag;
    }

    /**
     * Retrieve table name for custom option temporary aggregation data
     *
     * @return string
     */
    protected function getCustomOptionAggregateTable(): string
    {
        return $this->tableStrategy->getTableName('catalog_product_index_price_opt_agr');
    }

    /**
     * Retrieve table name for custom option prices data
     *
     * @return string
     */
    protected function getCustomOptionPriceTable(): string
    {
        return $this->tableStrategy->getTableName('catalog_product_index_price_opt');
    }

    /**
     * Prepare table structure for custom option temporary aggregation data
     *
     * @return void
     */
    protected function prepareCustomOptionAggregateTable()
    {
        $this->getConnection()->delete($this->getCustomOptionAggregateTable());
    }

    /**
     * Prepare table structure for custom option prices data
     *
     * @return void
     */
    protected function prepareCustomOptionPriceTable()
    {
        $this->getConnection()->delete($this->getCustomOptionPriceTable());
    }
}
