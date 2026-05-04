<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionInventory\Model\ResourceModel\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\ResourceConnection;

class CollectionUpdaterStock
{
    private ResourceConnection $resourceConnection;

    /**
     * CollectionUpdaterStock constructor.
     *
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    public function updateLinkedValueQty(string $productSku, float $qty): void
    {
        $connection    = $this->resourceConnection->getConnection();
        $tableName     = $this->resourceConnection->getTableName('catalog_product_option_type_value');
        $optionTypeIds = $this->getCollectionLinkedValues($productSku, $tableName);

        if ($optionTypeIds) {
            $connection->update(
                $tableName,
                ['qty' => $qty],
                "option_type_id IN (" . $optionTypeIds . ")"
            );
        }
    }

    public function getCollectionLinkedValues(string $productSku, string $tableName): string
    {
        $connection    = $this->resourceConnection->getConnection();
        $optionTypeIds = $connection->fetchCol(
            $connection->select()
                       ->from($tableName, 'option_type_id')
                       ->where('sku = "' . $productSku . '"')
                       ->where('manage_stock = 1')
        );

        return implode(', ', $optionTypeIds);
    }

    public function updateLinkedValueQtyAfterPlaceOrder(array $updateData): void
    {
        foreach ($updateData as $item) {
            $connection = $this->resourceConnection->getConnection();
            $tableName  = $this->resourceConnection->getTableName('catalog_product_option_type_value');
            $connection->update(
                $tableName,
                ['qty' => $item['qty']],
                "option_type_id = '" . $item['option_type_id'] . "'"
            );
        }
    }

    public function getOriginSku(string $sku): string
    {
        $connection   = $this->resourceConnection->getConnection();
        $productTable = $this->resourceConnection->getTableName('catalog_product_entity');

        $origSku = $connection->fetchOne(
            $connection->select()
                       ->from($productTable, [ProductInterface::SKU])
                       ->where(ProductInterface::SKU . ' = "' . $sku . '"')
        );

        return $origSku ?: '';
    }
}
