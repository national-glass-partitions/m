<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types = 1);

namespace MageWorx\OptionAdvancedPricing\Model;

use Magento\Catalog\Api\Data\ProductCustomOptionValuesInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use MageWorx\OptionAdvancedPricing\Api\SpecialPriceStorageInterface;
use MageWorx\OptionAdvancedPricing\Model\SpecialPrice as SpecialPriceModel;
use Magento\Framework\App\ResourceConnection;

class SpecialPriceStorage implements SpecialPriceStorageInterface
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
    public function getSpecialPriceData(ProductInterface $product, ProductCustomOptionValuesInterface $value): ?string
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

        if (isset($this->data[$productId][$valueId]['special_price'])) {
            return $this->data[$productId][$valueId]['special_price'];
        }

        return null;
    }

    /**
     * @inheritDoc
     */
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
        $tableName = $this->resource->getTableName(SpecialPriceModel::TABLE_NAME);

        $selectExpr = "SELECT "
            . SpecialPriceModel::COLUMN_OPTION_TYPE_ID . " AS "
            . SpecialPriceModel::FIELD_OPTION_TYPE_ID_ALIAS . ","
            . SpecialPriceModel::COLUMN_PRICE . ","
            . SpecialPriceModel::COLUMN_CUSTOMER_GROUP_ID . ","
            . SpecialPriceModel::COLUMN_PRICE_TYPE . ","
            . SpecialPriceModel::COLUMN_DATE_FROM . ","
            . SpecialPriceModel::COLUMN_DATE_TO . ","
            . SpecialPriceModel::COLUMN_COMMENT
            . " FROM " . $tableName
            . " WHERE option_type_id IN(" . implode(',', $valueIds) . ")";

        $connection = $this->resource->getConnection();
        $rawData = $connection->fetchAll($selectExpr);

        // Process the raw data into the desired format
        $processedData = [];
        foreach ($rawData as $row) {
            $optionTypeId = $row[SpecialPriceModel::FIELD_OPTION_TYPE_ID_ALIAS];
            unset($row[SpecialPriceModel::FIELD_OPTION_TYPE_ID_ALIAS]);

            $specialPriceData = [
                'price' => $row[SpecialPriceModel::COLUMN_PRICE] ?? '',
                'customer_group_id' => $row[SpecialPriceModel::COLUMN_CUSTOMER_GROUP_ID],
                'price_type' => $row[SpecialPriceModel::COLUMN_PRICE_TYPE],
                'date_from' => $row[SpecialPriceModel::COLUMN_DATE_FROM] ?? '',
                'date_to' => $row[SpecialPriceModel::COLUMN_DATE_TO] ?? '',
                'comment' => $row[SpecialPriceModel::COLUMN_COMMENT]
            ];

            if (!isset($processedData[$optionTypeId])) {
                $processedData[$optionTypeId] = ['special_price' => []];
            }
            $processedData[$optionTypeId]['special_price'][] = $specialPriceData;
        }

        // Convert special_price array to JSON
        foreach ($processedData as $optionTypeId => $data) {
            $processedData[$optionTypeId]['special_price'] = json_encode($data['special_price']);
        }

        $this->data[$product->getId()] = $processedData;
    }
}
