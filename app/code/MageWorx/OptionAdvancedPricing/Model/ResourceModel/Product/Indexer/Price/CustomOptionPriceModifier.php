<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See https://www.mageworx.com/terms-and-conditions for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionAdvancedPricing\Model\ResourceModel\Product\Indexer\Price;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Framework\DB\Select;

class CustomOptionPriceModifier
    extends \MageWorx\OptionFeatures\Model\ResourceModel\Product\Indexer\Price\CustomOptionPriceModifier
{
    protected \MageWorx\OptionAdvancedPricing\Helper\Data $advancedPricingHelper;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\EntityManager\MetadataPool $metadataPool,
        \Magento\Framework\DB\Sql\ColumnValueExpressionFactory $columnValueExpressionFactory,
        \Magento\Catalog\Helper\Data $dataHelper,
        \Magento\Framework\Indexer\Table\StrategyInterface $tableStrategy,
        \MageWorx\OptionAdvancedPricing\Helper\Data $advancedPricingHelper,
        string $connectionName = 'indexer'
    ) {
        parent::__construct(
            $resource,
            $metadataPool,
            $columnValueExpressionFactory,
            $dataHelper,
            $tableStrategy,
            $connectionName
        );
        $this->advancedPricingHelper = $advancedPricingHelper;
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
        if (!$this->advancedPricingHelper->isSpecialPriceEnabled()
            && !$this->advancedPricingHelper->isTierPriceEnabled()
        ) {
            return parent::getSelectForOptionsWithMultipleValues($sourceTable);
        }

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

        $maxUnsignedBigint  = '~0';
        $mwSpecialPriceExpr = null;
        $mwTierPriceExpr    = null;

        if ($this->advancedPricingHelper->isSpecialPriceEnabled()) {
            $this->joinSpecialPriceTablesToSelect($select);
            $mwSpecialPrice     = $this->getTotalSpecialPriceExpression();
            $mwSpecialPriceExpr = $connection->getIfNullSql($mwSpecialPrice, $maxUnsignedBigint);
        }

        if ($this->advancedPricingHelper->isTierPriceEnabled()) {
            $this->joinTierPriceTablesToSelect($select);
            $mwTierPrice     = $this->getTotalTierPriceExpression();
            $mwTierPriceExpr = $connection->getIfNullSql($mwTierPrice, $maxUnsignedBigint);
        }

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

        $minPriceRound = $this->columnValueExpressionFactory
            ->create([
                         'expression' => "ROUND(i.final_price * ({$optPriceValue} / 100), 4)"
                     ]);
        $minPriceExpr  = $connection->getCheckSql($optPriceTypeCondition, $optPriceValue, $minPriceRound);
        $minPrices     = [$minPriceExpr];

        if ($mwSpecialPriceExpr) {
            $minPrices[] = $mwSpecialPriceExpr;
        }
        if ($mwTierPriceExpr) {
            $minPrices[] = $mwTierPriceExpr;
        }

        $minPriceExpr     = $connection->getLeastSql($minPrices);
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
        $maxPrices     = [$maxPriceExpr];

        if ($mwSpecialPriceExpr) {
            $maxPrices[] = $mwSpecialPriceExpr;
        }
        if ($mwTierPriceExpr) {
            $maxPrices[] = $mwTierPriceExpr;
        }

        $maxPriceExpr = $connection->getLeastSql($maxPrices);
        $maxPrice     = $connection->getCheckSql(
            "(MIN(o.type)='radio' OR MIN(o.type)='drop_down')",
            "MAX({$maxPriceExpr})",
            "SUM({$maxPriceExpr})"
        );

        $tierPriceRound = $this->columnValueExpressionFactory
            ->create([
                         'expression' => "ROUND(i.tier_price * ({$optPriceValue} / 100), 4)"
                     ]);
        $tierPriceExpr  = $connection->getCheckSql($optPriceTypeCondition, $optPriceValue, $tierPriceRound);
        $tierPrices     = [$tierPriceExpr];

        if ($mwSpecialPriceExpr) {
            $tierPrices[] = $mwSpecialPriceExpr;
        }
        if ($mwTierPriceExpr) {
            $tierPrices[] = $mwTierPriceExpr;
        }

        $tierPriceExpr  = $connection->getLeastSql($tierPrices);
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

    protected function joinSpecialPriceTablesToSelect(Select $select): void
    {
        $currentDate = 'cwd.website_date';
        $select
            ->joinLeft(
            // calculate special price specified as Customer Group = `Specific Customer Group`
                ['special_price_1' => $this->getTable('mageworx_optionadvancedpricing_option_type_special_price')],
                'special_price_1.option_type_id = ot.option_type_id'
                . ' AND special_price_1.customer_group_id = i.customer_group_id'
                . ' AND (special_price_1.date_from IS NULL OR special_price_1.date_from <= ' . $currentDate . ')'
                . ' AND (special_price_1.date_to IS NULL OR special_price_1.date_to >= ' . $currentDate . ')',
                []
            )
            ->joinLeft(
            // calculate special price specified as Customer Group = `ALL GROUPS`
                ['special_price_2' => $this->getTable('mageworx_optionadvancedpricing_option_type_special_price')],
                'special_price_2.option_type_id = ot.option_type_id'
                . ' AND special_price_2.customer_group_id = ' . GroupInterface::CUST_GROUP_ALL
                . ' AND (special_price_2.date_from IS NULL OR special_price_2.date_from <= ' . $currentDate . ')'
                . ' AND (special_price_2.date_to IS NULL OR special_price_2.date_to >= ' . $currentDate . ')',
                []
            );
    }

    protected function joinTierPriceTablesToSelect(Select $select): void
    {
        $currentDate = 'cwd.website_date';
        $select
            ->joinLeft(
            // calculate tier price specified as Customer Group = `Specific Customer Group`
                ['tier_price_1' => $this->getTable('mageworx_optionadvancedpricing_option_type_tier_price')],
                'tier_price_1.option_type_id = ot.option_type_id'
                . ' AND tier_price_1.customer_group_id = i.customer_group_id AND tier_price_1.qty = 1'
                . ' AND (tier_price_1.date_from IS NULL OR tier_price_1.date_from <= ' . $currentDate . ')'
                . ' AND (tier_price_1.date_to IS NULL OR tier_price_1.date_to >= ' . $currentDate . ')',
                []
            )
            ->joinLeft(
            // calculate tier price specified as Customer Group = `ALL GROUPS`
                ['tier_price_2' => $this->getTable('mageworx_optionadvancedpricing_option_type_tier_price')],
                'tier_price_2.option_type_id = ot.option_type_id'
                . ' AND tier_price_2.customer_group_id = ' . GroupInterface::CUST_GROUP_ALL
                . ' AND tier_price_2.qty = 1'
                . ' AND (tier_price_2.date_from IS NULL OR tier_price_2.date_from <= ' . $currentDate . ')'
                . ' AND (tier_price_2.date_to IS NULL OR tier_price_2.date_to >= ' . $currentDate . ')',
                []
            );
    }

    /**
     * Get total special price expression
     *
     * @return \Zend_Db_Expr
     */
    protected function getTotalSpecialPriceExpression(): \Zend_Db_Expr
    {
        $maxUnsignedBigint = '~0';

        return $this->getConnection()->getCheckSql(
            implode(
                ' AND ',
                [
                    'special_price_1.option_type_special_price_id is NULL',
                    'special_price_2.option_type_special_price_id is NULL'
                ]
            ),
            'NULL',
            $this->getConnection()->getLeastSql(
                [
                    $this->getConnection()->getIfNullSql(
                        $this->getSpecialPriceExpressionForTable('special_price_1'),
                        $maxUnsignedBigint
                    ),
                    $this->getConnection()->getIfNullSql(
                        $this->getSpecialPriceExpressionForTable('special_price_2'),
                        $maxUnsignedBigint
                    )
                ]
            )
        );
    }

    /**
     * Get total tier price expression
     *
     * @return \Zend_Db_Expr
     */
    protected function getTotalTierPriceExpression(): \Zend_Db_Expr
    {
        $maxUnsignedBigint = '~0';

        return $this->getConnection()->getCheckSql(
            implode(
                ' AND ',
                [
                    'tier_price_1.option_type_tier_id is NULL',
                    'tier_price_2.option_type_tier_id is NULL'
                ]
            ),
            'NULL',
            $this->getConnection()->getLeastSql(
                [
                    $this->getConnection()->getIfNullSql(
                        $this->getTierPriceExpressionForTable('tier_price_1'),
                        $maxUnsignedBigint
                    ),
                    $this->getConnection()->getIfNullSql(
                        $this->getTierPriceExpressionForTable('tier_price_2'),
                        $maxUnsignedBigint
                    )
                ]
            )
        );
    }

    /**
     * Get special price expression for table
     *
     * @param string $tableAlias
     * @return \Zend_Db_Expr
     */
    protected function getSpecialPriceExpressionForTable($tableAlias): \Zend_Db_Expr
    {
        return $this->getConnection()->getCheckSql(
            sprintf("%s.price_type = 'fixed'", $tableAlias),
            sprintf('ROUND(%s.price * cwd.rate, 4)', $tableAlias),
            sprintf('ROUND(i.final_price * (1 - ROUND(%s.price * cwd.rate, 4) / 100), 4)', $tableAlias)
        );
    }

    /**
     * Get tier price expression for table
     *
     * @param string $tableAlias
     * @return \Zend_Db_Expr
     */
    protected function getTierPriceExpressionForTable($tableAlias): \Zend_Db_Expr
    {
        return $this->getConnection()->getCheckSql(
            sprintf("%s.price_type = 'fixed'", $tableAlias),
            sprintf('ROUND(%s.price * cwd.rate, 4)', $tableAlias),
            sprintf('ROUND(i.final_price * (1 - ROUND(%s.price * cwd.rate, 4) / 100), 4)', $tableAlias)
        );
    }
}
