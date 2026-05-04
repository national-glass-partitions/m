<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See https://www.mageworx.com/terms-and-conditions for license details.
 */

declare(strict_types=1);

namespace MageWorx\OptionHealthChecker\Model\ResourceModel;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;

class DataCleanerProcess
{
    public function queryConstructor(
        string $tableName,
        AdapterInterface $connection,
        Select $subSelect,
        string $targetField
    ): \Magento\Framework\DB\Select {
        $select = $connection->select();
        $select->from($connection->getTableName($tableName))
               ->where($targetField . ' NOT IN (?)', $subSelect);

        return $select;
    }

    public function selectEmptyRows(
        AdapterInterface $connection,
        string $tableName,
        string $targetField
    ): Select {
        $select = $connection->select();
        $select->from($connection->getTableName($tableName))
               ->where($targetField . ' IS NULL OR ' . $targetField . ' = "0" OR ' . $targetField . ' = "" ');

        return $select;
    }

    public function deleteEmptyRows(
        AdapterInterface $connection,
        string $tableName,
        Select $select
    ): void {

        $connection->query($select->deleteFromSelect($tableName));

    }

    public function selectSpecificDependencyRows(
        AdapterInterface $connection,
        string $tableName
    ): Select {
        $select = $connection->select();
        $select->from($connection->getTableName($tableName))
               ->where('dp_child_option_id = "0"')
               ->where('dp_child_option_type_id != "0"');

        return $select;
    }

    public function deleteSpecificDependencyRows(
        AdapterInterface $connection,
        string $tableName,
        Select $select
    ): void {
        $connection->query($select->deleteFromSelect($tableName));
    }
}
