<?php
/**
 * Copyright © 2018 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionBase\Api;

interface CollectionUpdaterInterface
{
    /**
     * Get from conditions for sql join
     * @param array $conditions
     * @return array
     */
    public function getFromConditions(array $conditions);

    /**
     * Get table name for sql join
     *
     * @param string $entityType
     * @return string
     */
    public function getTableName($entityType);

    /**
     * Get sql join's "ON" condition clause
     * Example:
     * @return string
     */
    public function getOnConditionsAsString();

    /**
     * Get columns for sql join
     * @return array
     */
    public function getColumns();

    /**
     * Get table alias for sql join
     * @return string
     */
    public function getTableAlias();

    /**
     * Determines the necessity of performing a join operation.
     *
     * This method checks if a join operation is necessary based on certain conditions.
     * It returns a boolean value indicating whether the join operation is necessary or not.
     *
     * @return bool True if a join operation is necessary, false otherwise. Default is true.
     */
    public function determineJoinNecessity(): bool;
}
