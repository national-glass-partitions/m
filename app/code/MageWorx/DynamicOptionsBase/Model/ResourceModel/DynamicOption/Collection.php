<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\DynamicOptionsBase\Model\ResourceModel\DynamicOption;

use MageWorx\DynamicOptionsBase\Api\Data\DynamicOptionInterface;

/**
 * Class Collection
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \MageWorx\DynamicOptionsBase\Model\DynamicOption::class,
            \MageWorx\DynamicOptionsBase\Model\ResourceModel\DynamicOption::class
        );
        $this->_map['fields'][DynamicOptionInterface::OPTION_ID] = 'main_table.' . 'id';
        $this->_setIdFieldName($this->_idFieldName);
    }

    /**
     * @param int $productId
     * @return array
     */
    public function getProductDynamicOptionIds($productId)
    {
        $connection       = $this->getConnection();
        $selectOldOptions = $connection->select()->from(
            $this->getTable($this->getMainTable()),
            'option_id'
        )->where('product_id = ?', $productId);

        return $connection->fetchCol($selectOldOptions);
    }
}
