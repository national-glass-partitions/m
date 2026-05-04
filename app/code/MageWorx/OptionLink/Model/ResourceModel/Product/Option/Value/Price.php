<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See https://www.mageworx.com/terms-and-conditions for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionLink\Model\ResourceModel\Product\Option\Value;

use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Catalog\Model\Product;
use MageWorx\OptionBase\Helper\Data as HelperBase;

class Price
{
    protected MetadataPool $metadataPool;
    protected ResourceConnection $resourceConnection;
    protected ?string $connectionName;
    protected ProductResource $productResource;
    protected HelperBase $helperBase;

    /**
     * @param MetadataPool $metadataPool
     * @param ResourceConnection $resourceConnection
     * @param ProductResource $productResource
     * @param HelperBase $helperBase
     * @throws \Exception
     */
    public function __construct(
        MetadataPool $metadataPool,
        ResourceConnection $resourceConnection,
        ProductResource $productResource,
        HelperBase $helperBase
    ) {
        $this->metadataPool       = $metadataPool;
        $this->resourceConnection = $resourceConnection;
        $metadata                 = $this->metadataPool->getMetadata(\Magento\Catalog\Api\Data\ProductInterface::class);
        $this->connectionName     = $metadata->getEntityConnectionName();
        $this->productResource    = $productResource;
        $this->helperBase         = $helperBase;
    }

    /**
     * @param array $optionTypeIds
     * @param int $productId
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function updateValuesByProductId(array $optionTypeIds, int $productId): bool
    {
        $productPrices = $this->getProductPrices($productId);

        if (empty($productPrices)) {
            return false;
        }

        $insertData = [];

        foreach ($optionTypeIds as $optionTypeId) {
            foreach ($productPrices as $storeId => $value) {
                $insertData[] = [
                    'option_type_id' => $optionTypeId,
                    'store_id'       => $storeId,
                    'price'          => $value,
                    'price_type'     => \Magento\Catalog\Model\Config\Source\Product\Options\Price::VALUE_FIXED
                ];
            }
        }

        $connection = $this->resourceConnection->getConnectionByName($this->connectionName);
        $tableName  = $this->resourceConnection->getTableName(
            'catalog_product_option_type_price',
            $this->connectionName
        );

        $connection->delete($tableName, ['option_type_id IN(?)' => $optionTypeIds]);
        $connection->insertMultiple($tableName, $insertData);

        return true;
    }

    /**
     * @param int $productId
     * @return array|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getProductPrices(int $productId): ?array
    {
        $metadata        = $this->metadataPool->getMetadata(\Magento\Catalog\Api\Data\ProductInterface::class);
        $linkField       = $metadata->getLinkField();
        $identifierField = $metadata->getIdentifierField();
        $connection      = $this->resourceConnection->getConnectionByName($this->connectionName);
        $priceAttribute  = $this->productResource->getAttribute(Product::PRICE);
        $select          = $connection->select();

        if ($this->helperBase->isEnterprise()) {
            $select
                ->from(
                    ['at_price' => $priceAttribute->getBackend()->getTable()],
                    ['store_id', 'value']
                )->join(
                    ['e' => $this->productResource->getEntityTable()],
                    "e.{$linkField} = at_price.{$linkField}",
                    []
                )->where('at_price.attribute_id = ?', $priceAttribute->getId())
                ->where("e.{$identifierField} = ?", $productId);
        } else {
            $select
                ->from($priceAttribute->getBackend()->getTable(), ['store_id', 'value'])
                ->where('attribute_id = ?', $priceAttribute->getAttributeId())
                ->where("{$linkField} = ?", $productId);
        }

        $data = [];

        foreach ($connection->fetchAll($select) as $row) {
            $data[$row['store_id']] = $row['value'];
        }

        return $data ?: null;
    }
}
