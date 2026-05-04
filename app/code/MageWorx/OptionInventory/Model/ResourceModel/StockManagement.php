<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionInventory\Model\ResourceModel;

/**
 * StockManagement Resource model.
 * @package MageWorx\OptionInventory\Model\ResourceModel
 */
class StockManagement extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    protected \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry;

    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('catalog_product_option_type_value', 'option_type_id');
    }

    /**
     * StockManagement constructor.
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param null $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        $connectionName = null
    ) {
        $this->stockRegistry = $stockRegistry;
        parent::__construct($context, $connectionName);
    }

    /**
     * Correct particular stock option value and product qty.
     *
     * @param int[] $items
     * @param string $operator +/-
     * @return void|$this
     */
    public function correctItemsQty(array $items, $operator)
    {
        $options = isset($items['options']) ? $items['options'] : [];
        $products = isset($items['products']) ? $items['products'] : [];

        $this->correctOptionsQty($options, $operator);
        $this->correctProductsQty($products);
    }

    /**
     * Correct particular stock option value qty based on operator.
     *
     * @param array $items
     * @param string $operator +/-
     * @return $this
     */
    protected function correctOptionsQty($items, $operator)
    {
        if (empty($items)) {
            return $this;
        }

        $connection = $this->getConnection();
        $conditions = [];
        foreach ($items as $optionTypeId => $qty) {
            $case = $connection->quoteInto('?', $optionTypeId);
            $result = $connection->quoteInto("qty{$operator}?", $qty);
            $conditions[$case] = $result;
        }

        $value = $connection->getCaseSql('option_type_id', $conditions, 'qty');
        $where = ['option_type_id IN (?)' => array_keys($items)];

        $connection->beginTransaction();
        $connection->update($this->getTable('catalog_product_option_type_value'), ['qty' => $value], $where);
        $connection->commit();

        return $this;
    }

    /**
     * Correct particular stock product qty.
     *
     * @param $items
     * @return $this
     */
    protected function correctProductsQty($items)
    {
        if (empty($items)) {
            return $this;
        }

        foreach ($items as $item) {
            if(!isset($item['product']) || !isset($item['productStock']) || !isset($item['qty'])) {
                continue;
            }

            $product = $item['product'];
            $productStock = $item['productStock'];
            $qty = $item['qty'];

            $productStock->setQty($qty);
            $this->stockRegistry->updateStockItemBySku($product->getSku(), $productStock);
        }

        return $this;
    }
}
