<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionSwatches\Model\ResourceModel\Catalog;

use Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator;
use Magento\Framework\App\ResourceConnection;

class ProductUrls
{
    private ResourceConnection $resourceConnection;

    /**
     * ProductPathCollection constructor.
     *
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param $productSkus
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductUrlsBySku($productSkus)
    {
        $connection = $this->resourceConnection->getConnection();

        $selectProductPath = $connection->select()->from(
            ['e' => $this->resourceConnection->getTableName('catalog_product_entity')],
            [ 'sku' ]
        )->joinLeft(
            ['url_rewrite' => $this->resourceConnection->getTableName('url_rewrite')],
            'e.entity_id = url_rewrite.entity_id AND url_rewrite.metadata IS NULL'
            . $connection->quoteInto(' AND url_rewrite.entity_type = ?', ProductUrlRewriteGenerator::ENTITY_TYPE),
            ['url' => 'request_path']
        )->where("e.sku IN (?)", $productSkus);

        return  $connection->fetchAssoc($selectProductPath);
    }
}
