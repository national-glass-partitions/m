<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See https://www.mageworx.com/terms-and-conditions for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionDependency\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;


class ProcessDeleteOldData
{
    private ResourceConnection $resourceConnection;
    private LoggerInterface $logger;

    /**
     * ProductPathCollection constructor.
     *
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->logger                    = $logger;
    }

    public function deleteOldData(
        array $productIds,
        array $groupIds,
        string $columnToCompare,
        bool $isAfterTemplateSave,
        string $tableName
    ): void {
        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()
                             ->reset()
                             ->from(['dep' => $this->resourceConnection->getTableName($tableName)])
                             ->joinLeft(
                                 ['cpo' => $this->resourceConnection->getTableName('catalog_product_option')],
                                 'cpo.option_id = ' . $columnToCompare,
                                 []
                             );
        if ($isAfterTemplateSave && $groupIds) {
            $select->where(
                'dep.group_id IN (' . implode(',', $groupIds) . ') AND ' .
                'dep.product_id IN (' . implode(',', $productIds) . ')'
            );
        } else {
            $select->where('dep.product_id IN (' . implode(',', $productIds) . ')');
        }

        $connection->beginTransaction();
        try {
            $connection->query($select->deleteFromSelect('dep'));
            $connection->commit();
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $connection->rollBack();
            throw $e;
        }
    }
}
