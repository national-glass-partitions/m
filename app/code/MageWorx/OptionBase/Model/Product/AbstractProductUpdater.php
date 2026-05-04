<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionBase\Model\Product;

use Magento\Framework\App\ResourceConnection;
use MageWorx\OptionBase\Api\ProductCollectionUpdaterInterface;
use MageWorx\OptionBase\Helper\Data as Helper;
use MageWorx\OptionBase\Helper\System as SystemHelper;

abstract class AbstractProductUpdater implements ProductCollectionUpdaterInterface
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
     * Get product table name for sql join
     *
     * @return string
     */
    public function getProductTableName()
    {
        return '';
    }

    /**
     * Get product table name for sql join
     *
     * @return string
     */
    public function getTemplateTableName()
    {
        return '';
    }

    /**
     * Get columns for sql join
     * @return array
     */
    public function getColumns()
    {
        return [];
    }

    /**
     * Get table alias for sql join
     * @return string
     */
    public function getTableAlias()
    {
        return '';
    }
}
