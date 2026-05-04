<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionBase\Api;

interface ProductCollectionUpdaterInterface
{
    /**
     * Get product table name for sql join
     *
     * @return string
     */
    public function getProductTableName();
    /**
     * Get product table name for sql join
     *
     * @return string
     */
    public function getTemplateTableName();

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
}
