<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionAdvancedPricing\Model;

use Magento\Catalog\Api\Data\ProductCustomOptionValuesInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use MageWorx\OptionAdvancedPricing\Api\TierPriceStorageInterface;
use Magento\Framework\App\ResourceConnection;
use MageWorx\OptionAdvancedPricing\Model\TierPrice as TierPriceModel;

class TierPriceStorage implements TierPriceStorageInterface
{
    protected ResourceConnection $resource;
    protected array              $data = [];

    public function __construct(
        ResourceConnection $resource,
        array              $data = []
    ) {
        $this->resource = $resource;
        $this->data     = $data;
    }

    /**
     * @inheritDoc
     */
    public function getTierPriceData(ProductInterface $product, ProductCustomOptionValuesInterface $value): ?string
    {
        $productId = (int)$product->getId();
        if (!$productId) {
            return null;
        }

        $optionId = $value->getOptionId();
        if (!$optionId) {
            return null;
        }

        $valueId = $value->getOptionTypeId();
        if (!$valueId) {
            return null;
        }

        if (!isset($this->data[$productId])) {
            $this->loadData($product);
        }

        if (isset($this->data[$productId][$valueId]['tier_price'])) {
            return $this->data[$productId][$valueId]['tier_price'];
        }

        return null;
    }

    public function loadData(ProductInterface $product): void
    {
        // Get all custom options from product
        $productOptions = $product->getOptions();
        if (!$productOptions) {
            $this->data[$product->getId()] = [];
            return;
        }

        // Collect all value ids
        $valueIds = [];
        foreach ($productOptions as $option) {
            $values = $option->getValues();
            if (!$values) {
                continue;
            }

            foreach ($values as $value) {
                $valueIds[] = $value->getId();
            }
        }

        // Load data from the database with value ids filter
        $tableName = $this->resource->getTableName(TierPriceModel::TABLE_NAME);

        $selectExpr = "SELECT "
            . TierPriceModel::COLUMN_OPTION_TYPE_ID . " AS "
            . TierPriceModel::FIELD_OPTION_TYPE_ID_ALIAS . ","
            . TierPriceModel::COLUMN_PRICE . ","
            . TierPriceModel::COLUMN_CUSTOMER_GROUP_ID . ","
            . TierPriceModel::COLUMN_PRICE_TYPE . ","
            . TierPriceModel::COLUMN_DATE_FROM . ","
            . TierPriceModel::COLUMN_DATE_TO . ","
            . TierPriceModel::COLUMN_QTY
            . " FROM " . $tableName
            . " WHERE option_type_id IN(" . implode(',', $valueIds) . ")";

        $connection = $this->resource->getConnection();
        $rawData = $connection->fetchAll($selectExpr);

        // Process the raw data into the desired format
        $processedData = [];
        foreach ($rawData as $row) {
            $optionTypeId = $row[TierPriceModel::FIELD_OPTION_TYPE_ID_ALIAS];
            unset($row[TierPriceModel::FIELD_OPTION_TYPE_ID_ALIAS]);

            $tierPriceData = [
                'price' => $row[TierPriceModel::COLUMN_PRICE] ?? '',
                'customer_group_id' => $row[TierPriceModel::COLUMN_CUSTOMER_GROUP_ID],
                'price_type' => $row[TierPriceModel::COLUMN_PRICE_TYPE],
                'date_from' => $row[TierPriceModel::COLUMN_DATE_FROM] ?? '',
                'date_to' => $row[TierPriceModel::COLUMN_DATE_TO] ?? '',
                'qty' => $row[TierPriceModel::COLUMN_QTY]
            ];

            if (!isset($processedData[$optionTypeId])) {
                $processedData[$optionTypeId] = ['tier_price' => []];
            }
            $processedData[$optionTypeId]['tier_price'][] = $tierPriceData;
        }

        // Convert tier_price array to JSON
        foreach ($processedData as $optionTypeId => $data) {
            $processedData[$optionTypeId]['tier_price'] = json_encode($data['tier_price']);
        }

        $this->data[$product->getId()] = $processedData;
    }
}
