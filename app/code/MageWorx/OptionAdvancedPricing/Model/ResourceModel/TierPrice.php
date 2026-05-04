<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionAdvancedPricing\Model\ResourceModel;

use Magento\Customer\Api\Data\GroupInterface;
use Magento\Framework\EntityManager\MetadataPool;
use MageWorx\OptionAdvancedPricing\Helper\Data as Helper;
use MageWorx\OptionAdvancedPricing\Model\TierPrice as TierPriceModel;
use MageWorx\OptionBase\Helper\Data as HelperBase;
use Magento\Framework\Model\ResourceModel\Db\Context;

class TierPrice extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected MetadataPool $metadataPool;
    protected HelperBase $helperBase;

    /**
     * @param Context $context
     * @param MetadataPool $metadataPool
     * @param HelperBase $helperBase
     * @param string|null $connectionName
     * @throws \Exception
     */
    public function __construct(
        Context $context,
        MetadataPool $metadataPool,
        HelperBase $helperBase,
        string $connectionName = null
    ) {
        $this->metadataPool = $metadataPool;
        $this->helperBase   = $helperBase;

        if ($connectionName === null) {
            $metadata       = $this->metadataPool->getMetadata(\Magento\Catalog\Api\Data\ProductInterface::class);
            $connectionName = $metadata->getEntityConnectionName();
        }
        parent::__construct($context, $connectionName);
    }

    /**
     * Initialize main table and table id field
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            TierPriceModel::TABLE_NAME,
            TierPriceModel::COLUMN_OPTION_TYPE_TIER_PRICE_ID
        );
    }

    /**
     * @param array $optionTypeIds
     * @param int $productId
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function updateValuesByProductId(array $optionTypeIds, int $productId): bool
    {
        $productTierPrices = $this->getProductTierPrices($productId);

        if (empty($productTierPrices)) {
            return false;
        }

        $insertData = [];

        foreach ($optionTypeIds as $optionTypeId) {
            foreach ($productTierPrices as $data) {
                $insertData[] = [
                    TierPriceModel::COLUMN_OPTION_TYPE_ID    => $optionTypeId,
                    TierPriceModel::COLUMN_CUSTOMER_GROUP_ID => ($data['all_groups'] > 0)
                        ? GroupInterface::CUST_GROUP_ALL : $data['customer_group_id'],
                    TierPriceModel::COLUMN_QTY               => $data['qty'],
                    TierPriceModel::COLUMN_PRICE             => ((float)$data['value'] == 0)
                        ? $data['percentage_value'] : $data['value'],
                    TierPriceModel::COLUMN_PRICE_TYPE        => ((float)$data['value'] == 0)
                        ? Helper::PRICE_TYPE_PERCENTAGE_DISCOUNT : Helper::PRICE_TYPE_FIXED,
                    TierPriceModel::COLUMN_DATE_FROM         => null,
                    TierPriceModel::COLUMN_DATE_TO           => null
                ];
            }
        }

        $connection = $this->getConnection();
        $tableName  = $this->getMainTable();

        $connection->delete($tableName, [TierPriceModel::COLUMN_OPTION_TYPE_ID . ' IN(?)' => $optionTypeIds]);
        $connection->insertMultiple($tableName, $insertData);

        return true;
    }

    /**
     * @param int $productId
     * @return array
     * @throws \Exception
     */
    protected function getProductTierPrices(int $productId): array
    {
        $metadata        = $this->metadataPool->getMetadata(\Magento\Catalog\Api\Data\ProductInterface::class);
        $linkField       = $metadata->getLinkField();
        $identifierField = $metadata->getIdentifierField();
        $connection      = $this->getConnection();
        $select          = $connection->select();

        if ($this->helperBase->isEnterprise()) {
            $select
                ->from(
                    ['at_tier_price' => $this->getTable('catalog_product_entity_tier_price')],
                    ['all_groups', 'customer_group_id', 'qty', 'value', 'website_id', 'percentage_value']
                )->join(
                    ['e' => $this->getTable('catalog_product_entity')],
                    "e.{$linkField} = at_tier_price.{$linkField}",
                    []
                )->where('at_tier_price.website_id = 0')
                ->where("e.{$identifierField} = ?", $productId);
        } else {
            $select
                ->from(
                    $this->getTable('catalog_product_entity_tier_price'),
                    ['all_groups', 'customer_group_id', 'qty', 'value', 'website_id', 'percentage_value']
                )
                ->where('website_id = 0')
                ->where("{$linkField} = ?", $productId);
        }

        return $connection->fetchAll($select);
    }
}
