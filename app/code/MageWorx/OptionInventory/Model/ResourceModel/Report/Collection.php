<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionInventory\Model\ResourceModel\Report;

use Magento\Catalog\Api\Data\ProductInterface;
use MageWorx\OptionBase\Helper\Data as OptionBaseHelper;

/**
 * Class Collection
 * @package MageWorx\OptionInventory\Model\ResourceModel\Report
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected OptionBaseHelper $helper;

    /**
     * Map field to alias
     *
     * @var array
     */
    protected $_map = ['fields' =>
        [
            'product_id'   => 'cpo.product_id',
            'product_name' => 'pn.product_name',
            'product_sku'  => 'cpe.sku',
            'option_name'  => 'cpot.title',
            'value_name'   => 'cpott.title',
            'qty'          => 'main_table.qty',
            'manage_stock' => 'main_table.manage_stock'
        ]
    ];

    /**
     * @var string
     */
    protected $_idFieldName = 'option_type_id';

    /**
     * Collection constructor.
     *
     * @param \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Framework\DB\Adapter\AdapterInterface|null $connection
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb|null $resource
     * @param OptionBaseHelper $helper
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        OptionBaseHelper $helper,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ) {
        $this->helper = $helper;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
    }

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('MageWorx\OptionInventory\Model\Report', 'MageWorx\OptionInventory\Model\ResourceModel\Report');
    }

    /**
     * Add Option Name
     *
     * @return $this
     */
    public function addOptionName()
    {
        $this->getSelect()
            ->joinLeft(
                ['cpot' => $this->getTable('catalog_product_option_title')],
                'cpot.option_id = main_table.option_id AND cpot.store_id = 0',
                ['option_name'=>'cpot.title']
            );
        return $this;
    }

    /**
     * Add Option Value Name
     *
     * @return $this
     */
    public function addValueName()
    {
        $this->getSelect()
            ->joinLeft(
                ['cpott' => $this->getTable('catalog_product_option_type_title')],
                'cpott.option_type_id = main_table.option_type_id AND cpott.store_id = 0',
                ['value_name'=>'cpott.title']
            );
        return $this;
    }

    /**
     * Add Product Sku
     *
     * @return $this
     */
    public function addProductSku()
    {
        $this->getSelect()
            ->joinLeft(
                ['cpo' => $this->getTable('catalog_product_option')],
                'cpo.option_id = main_table.option_id',
                ['cpo.product_id']
            )
            ->joinLeft(
                ['cpe' => $this->getTable('catalog_product_entity')],
                'cpe.entity_id = cpo.product_id',
                ['product_sku'=>'cpe.sku']
            );
        return $this;
    }

    /**
     * Add Product Name
     *
     * @return $this
     */
    public function addProductName()
    {
        $this->getSelect()
            ->joinLeft(
                ['pn' => new \Zend_Db_Expr($this->getTableProductName())],
                'pn.'.$this->helper->getLinkField(ProductInterface::class).' = cpo.product_id',
                ['product_name'=>'pn.product_name']
            );
        return $this;
    }

    /**
     * Retrieve table with product name
     *
     * @return string
     */
    private function getTableProductName()
    {
        $tableCPEV = $this->getTable('catalog_product_entity_varchar');
        $tableEET = $this->getTable('eav_entity_type');
        $tableEA = $this->getTable('eav_attribute');

        $cpevPrimaryField = $this->helper->isEnterprise() ? 'value_id' : 'entity_id';
        $cpevRowIdField = $this->helper->isEnterprise() ? 'cpev.row_id as row_id,' : '';

        return '(SELECT cpev.'.$cpevPrimaryField.' as entity_id, '.$cpevRowIdField.' cpev.attribute_id as attribute_id, cpev.value as product_name
                FROM '.$tableCPEV.' as cpev
                WHERE attribute_id = (
                   SELECT e.attribute_id
                   FROM '.$tableEA.' e
                   LEFT JOIN '.$tableEET.' AS t ON e.entity_type_id = t.entity_type_id
                   WHERE e.attribute_code = \'name\' AND t.entity_type_code = \'catalog_product\'
                ) AND cpev.store_id = 0)';
    }
}
