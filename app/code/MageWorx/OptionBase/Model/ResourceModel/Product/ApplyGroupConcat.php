<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionBase\Model\ResourceModel\Product;

use Magento\Framework\App\ResourceConnection;

/**
 * This class using to extend system params if needed
 */
class ApplyGroupConcat
{
    protected ResourceConnection $resource;
    protected int $groupConcatMaxLen = 100000;

    public function __construct(
        ResourceConnection $resource
    ) {
        $this->resource = $resource;
    }

    /**
     * The method extend group_concat_max_len for our SQL queries, if needed
     *
     * @return void
     */
    public function applyGroupConcatMaxLen(): void
    {
        $connection                    = $this->resource->getConnection();
        $currentGroupConcatMaxLenValue = $connection->fetchOne('SELECT @@session.group_concat_max_len');

        if ($currentGroupConcatMaxLenValue < $this->groupConcatMaxLen) {
            $connection->query('SET SESSION group_concat_max_len = ' . $this->groupConcatMaxLen . ' ;');
        }
    }
}
