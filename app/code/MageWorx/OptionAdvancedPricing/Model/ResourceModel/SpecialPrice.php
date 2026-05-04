<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See https://www.mageworx.com/terms-and-conditions for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionAdvancedPricing\Model\ResourceModel;

use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Framework\EntityManager\MetadataPool;
use MageWorx\OptionAdvancedPricing\Helper\Data as Helper;
use MageWorx\OptionAdvancedPricing\Model\SpecialPrice as SpecialPriceModel;
use Magento\Framework\Model\ResourceModel\Db\Context;
use MageWorx\OptionBase\Helper\Data as HelperBase;

class SpecialPrice extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected MetadataPool $metadataPool;
    protected ProductResource $productResource;
    protected HelperBase $helperBase;

    /**
     * @param Context $context
     * @param MetadataPool $metadataPool
     * @param ProductResource $productResource
     * @param HelperBase $helperBase
     * @param string|null $connectionName
     * @throws \Exception
     */
    public function __construct(
        Context $context,
        MetadataPool $metadataPool,
        ProductResource $productResource,
        HelperBase $helperBase,
        string $connectionName = null
    ) {
        $this->metadataPool    = $metadataPool;
        $this->productResource = $productResource;
        $this->helperBase      = $helperBase;

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
            SpecialPriceModel::TABLE_NAME,
            SpecialPriceModel::COLUMN_OPTION_TYPE_SPECIAL_PRICE_ID
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
        $productSpecialPrices = $this->getProductSpecialPrices($productId);

        if (empty($productSpecialPrices)) {
            return false;
        }

        $insertData = [];
        $comments   = $this->getComments($optionTypeIds);

        foreach ($optionTypeIds as $optionTypeId) {
            foreach ($productSpecialPrices as $storeId => $data) {
                $comment      = empty($comments[$optionTypeId]) ? '' : $comments[$optionTypeId];
                $insertData[] = [
                    SpecialPriceModel::COLUMN_OPTION_TYPE_ID    => $optionTypeId,
                    SpecialPriceModel::COLUMN_CUSTOMER_GROUP_ID => GroupInterface::CUST_GROUP_ALL,
                    SpecialPriceModel::COLUMN_PRICE             => $data['value'],
                    SpecialPriceModel::COLUMN_PRICE_TYPE        => Helper::PRICE_TYPE_FIXED,
                    SpecialPriceModel::COLUMN_COMMENT           => $comment,
                    SpecialPriceModel::COLUMN_DATE_FROM         => $data['date_from'],
                    SpecialPriceModel::COLUMN_DATE_TO           => $data['date_to']
                ];
            }
        }

        $connection = $this->getConnection();
        $tableName  = $this->getMainTable();

        $connection->delete($tableName, [SpecialPriceModel::COLUMN_OPTION_TYPE_ID . ' IN(?)' => $optionTypeIds]);
        $connection->insertMultiple($tableName, $insertData);

        return true;
    }

    /**
     * @param int $productId
     * @return array|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getProductSpecialPrices(int $productId): ?array
    {
        $metadata              = $this->metadataPool->getMetadata(\Magento\Catalog\Api\Data\ProductInterface::class);
        $linkField             = $metadata->getLinkField();
        $identifierField       = $metadata->getIdentifierField();
        $connection            = $this->getConnection();
        $specialPriceAttribute = $this->productResource->getAttribute('special_price');
        $select                = $connection->select();

        if ($this->helperBase->isEnterprise()) {
            $select
                ->from(
                    ['at_special_price' => $specialPriceAttribute->getBackend()->getTable()],
                    ['store_id', 'value']
                )->join(
                    ['e' => $this->productResource->getEntityTable()],
                    "e.{$linkField} = at_special_price.{$linkField}",
                    []
                )->where('at_special_price.attribute_id = ?', $specialPriceAttribute->getId())
                ->where('at_special_price.store_id = 0')
                ->where('at_special_price.value IS NOT NULL')
                ->where("e.{$identifierField} = ?", $productId);
        } else {
            $specialFromDateAttribute = $this->productResource->getAttribute('special_from_date');
            $specialToDateAttribute   = $this->productResource->getAttribute('special_to_date');
            $select
                ->from(
                    ['at_special_price' => $specialPriceAttribute->getBackend()->getTable()],
                    ['store_id' => 'at_special_price.store_id', 'value' => 'at_special_price.value']
                )->joinLeft(
                    ['at_special_from_date' => $specialFromDateAttribute->getBackend()->getTable()],
                    "at_special_from_date.{$linkField} = at_special_price.{$linkField} AND "
                    . 'at_special_from_date.store_id = at_special_price.store_id AND '
                    . 'at_special_from_date.attribute_id = ' . (int)$specialFromDateAttribute->getId(),
                    ['date_from' => $connection->getDatePartSql('DATE(at_special_from_date.value)')]
                )->joinLeft(
                    ['at_special_to_date' => $specialToDateAttribute->getBackend()->getTable()],
                    "at_special_to_date.{$linkField} = at_special_price.{$linkField} AND "
                    . 'at_special_to_date.store_id = at_special_price.store_id AND '
                    . 'at_special_to_date.attribute_id = ' . (int)$specialToDateAttribute->getId(),
                    ['date_to' => $connection->getDatePartSql('DATE(at_special_to_date.value)')]
                )->where('at_special_price.attribute_id = ?', $specialPriceAttribute->getId())
                ->where('at_special_price.store_id = 0')
                ->where('at_special_price.value IS NOT NULL')
                ->where("at_special_price.{$linkField} = ?", $productId);
        }

        $data = [];

        foreach ($connection->fetchAll($select) as $row) {
            $data[$row['store_id']] = [
                'value'     => $row['value'],
                'date_from' => empty($row['date_from']) ? null : $row['date_from'],
                'date_to'   => empty($row['date_to']) ? null : $row['date_to']
            ];
        }

        return $data ?: null;
    }

    /**
     * @param array $optionTypeIds
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getComments(array $optionTypeIds): array
    {
        $connection = $this->getConnection();
        $select     = $connection->select();
        $select
            ->from($this->getMainTable(), [SpecialPriceModel::COLUMN_OPTION_TYPE_ID, SpecialPriceModel::COLUMN_COMMENT])
            ->where(SpecialPriceModel::COLUMN_OPTION_TYPE_ID . ' IN(?)', $optionTypeIds);

        return $connection->fetchPairs($select);
    }
}
