<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionBase\Model\Product\Option;

use Magento\Framework\App\ResourceConnection;
use MageWorx\OptionBase\Api\CollectionUpdaterInterface;
use MageWorx\OptionBase\Helper\Data as Helper;
use MageWorx\OptionBase\Helper\System as SystemHelper;

abstract class AbstractUpdater implements CollectionUpdaterInterface
{
    protected SystemHelper $systemHelper;
    protected Helper $helper;
    protected ResourceConnection $resource;

    public function __construct(
        ResourceConnection $resource,
        Helper $helper,
        SystemHelper $systemHelper
    ) {
        $this->resource = $resource;
        $this->helper = $helper;
        $this->systemHelper = $systemHelper;
    }

    /**
     * Get from conditions for sql join
     *
     * @param array $conditions
     * @return array
     */
    public function getFromConditions(array $conditions)
    {
        return [];
    }

    /**
     * Get table name for sql join
     *
     * @param string $entityType
     * @return string
     */
    public function getTableName($entityType)
    {
        return '';
    }

    /**
     * Get sql join's "ON" condition clause
     *
     * @return string
     */
    public function getOnConditionsAsString()
    {
        return '';
    }

    /**
     * Get columns for sql join
     *
     * @return array
     */
    public function getColumns()
    {
        return [];
    }

    /**
     * Get table alias for sql join
     *
     * @return string
     */
    public function getTableAlias()
    {
        return '';
    }

    /**
     * Determines whether a join is necessary.
     *
     * This method utilizes the original old logic to determine if a join is necessary.
     * By default, it always returns true.
     * Allows you to exclude unnecessary joins from certain updaters.
     *
     * @return bool Returns true if a join is necessary, otherwise false.
     */
    public function determineJoinNecessity(): bool
    {
        return true;
    }
}
