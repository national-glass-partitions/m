<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See https://www.mageworx.com/terms-and-conditions for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionLink\Model\ResourceModel\Product\Option\Value;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;

class LinkedAttribute
{
    protected MetadataPool $metadataPool;
    protected ResourceConnection $resourceConnection;
    protected ?string $connectionName;

    /**
     * @param MetadataPool $metadataPool
     * @param ResourceConnection $resourceConnection
     * @throws \Exception
     */
    public function __construct(MetadataPool $metadataPool, ResourceConnection $resourceConnection)
    {
        $this->metadataPool       = $metadataPool;
        $this->resourceConnection = $resourceConnection;
        $metadata                 = $this->metadataPool->getMetadata(\Magento\Catalog\Api\Data\ProductInterface::class);
        $this->connectionName     = $metadata->getEntityConnectionName();
    }

    public function getOptionTypeIdsBySku(string $sku): ?array
    {
        $connection = $this->resourceConnection->getConnectionByName($this->connectionName);
        $select     = $connection->select();
        $select
            ->from(
                $this->resourceConnection->getTableName('catalog_product_option_type_value', $this->connectionName),
                ['option_type_id']
            )->where('sku = ? ', $sku);

        $ids = $connection->fetchCol($select);

        return empty($ids) ? null : array_values($ids);
    }

    /**
     * @param string $sku
     * @return array|null
     * @throws \Exception
     */
    public function getProductIdsBySku(string $sku): ?array
    {
        $metadata   = $this->metadataPool->getMetadata(\Magento\Catalog\Api\Data\ProductInterface::class);
        $linkField  = $metadata->getLinkField();
        $connection = $this->resourceConnection->getConnectionByName($this->connectionName);
        $select     = $connection->select();
        $select
            ->from(
                ['e' => $this->resourceConnection->getTableName('catalog_product_entity', $this->connectionName)],
                [$metadata->getIdentifierField()]
            )->join(
                ['cpo' => $this->resourceConnection->getTableName('catalog_product_option', $this->connectionName)],
                "e.{$linkField} = cpo.product_id",
                []
            )->join(
                [
                    'cpot' => $this->resourceConnection->getTableName(
                        'catalog_product_option_type_value',
                        $this->connectionName
                    )
                ],
                'cpot.option_id = cpo.option_id' . $connection->quoteInto(' AND cpot.sku = ?', $sku),
                []
            )->distinct();

        $productIds = $connection->fetchCol($select);

        return empty($productIds) ? null : array_values($productIds);
    }
}
