<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionTemplates\Model\ResourceModel\Group;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'group_id';

    /**
     * Map field to alias
     *
     * @var array
     */
    protected $_map = ['fields' =>
        [
            'group_id'   => 'main_table.group_id',
            'title' => 'main_table.title'
        ]
    ];

    /**
     * Initialization
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('MageWorx\OptionTemplates\Model\Group', 'MageWorx\OptionTemplates\Model\ResourceModel\Group');
    }

    /**
     *
     * @return \MageWorx\OptionTemplates\Model\ResourceModel\Group\Collection
     */
    public function addProductCount()
    {
        $this->getSelect()
            ->joinLeft(
                ['relation_table' => $this->getTable('mageworx_optiontemplates_relation')],
                'relation_table.group_id = main_table.group_id',
                ['products' => 'COUNT(DISTINCT relation_table.product_id)']
            )->group('main_table.group_id');

        return $this;
    }

    /**
     *
     * @return $this
     */
    public function addHideFilter()
    {
        return $this->addFieldToFilter('is_active', ['in' => \MageWorx\OptionTemplates\Model\Group::VISIBLE_HIDE]);
    }

    /**
     *
     * @return $this
     */
    public function addShowFilter()
    {
        return $this->addFieldToFilter('is_active', ['in' => \MageWorx\OptionTemplates\Model\Group::VISIBLE_SHOW]);
    }

    /**
     * Add product filter
     *
     * @param int $productId
     * @return \MageWorx\OptionTemplates\Model\ResourceModel\Group\Collection
     */
    public function addProductFilter($productId)
    {
        $this->getSelect()
            ->joinLeft(
                ['relation_table' => $this->getTable('mageworx_optiontemplates_relation')],
                'main_table.group_id = relation_table.group_id',
                ['product_id']
            )->where('relation_table.product_id = ?', $productId);

        return $this;
    }

    /**
     *
     * @param   string $valueField
     * @param   string $labelField
     * @param   array $additional
     * @return  array
     */
    protected function _toOptionArray($valueField = null, $labelField = 'name', $additional = [])
    {
        return parent::_toOptionArray('group_id', 'title');
    }

    /**
     *
     * @param   string $valueField
     * @param   string $labelField
     * @return  array
     */
    protected function _toOptionHash($valueField = null, $labelField = 'name')
    {
        return parent::_toOptionHash('group_id', 'title');
    }
}
