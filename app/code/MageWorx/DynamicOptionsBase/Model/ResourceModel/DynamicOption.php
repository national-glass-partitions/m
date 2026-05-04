<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\DynamicOptionsBase\Model\ResourceModel;

/**
 * Class DynamicOption
 */
class DynamicOption extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    const DYNAMIC_OPTIONS_TABLE = 'mageworx_dynamic_options';

    protected $_idFieldName = 'id';
    protected string $_eventPrefix = 'mageworx_dynamic_options_collection';
    protected string $_eventObject = 'dynamic_options_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            self::DYNAMIC_OPTIONS_TABLE,
            'id'
        );
    }

    /**
     * @param int $oldProductId
     * @param int $newProductId
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function duplicate($oldProductId, $newProductId)
    {
        $oldOptions = $this->getOptionTitles($oldProductId);
        $newOptions = $this->getOptionTitles($newProductId);

        $newOptionsIds = [];
        foreach ($newOptions as $key => $newOption) {
            if (isset($oldOptions[$key])) {
                $newOptionsIds[$oldOptions[$key]] = $newOption;
            }
        }

        $connection = $this->getConnection();
        $selectDynamicOptions = $connection->select()->from($this->getMainTable())->where(
            'product_id = (?)',
            $oldProductId
        );

        $data  = $connection->fetchAll($selectDynamicOptions);
        foreach ($data as $key => $row) {
            $data[$key]['product_id'] = $newProductId;
            $data[$key]['option_id'] = $newOptionsIds[$data[$key]['option_id']];

            unset($data[$key]['id']);
        }

        if (!empty($data)) {
            $this->getConnection()->insertMultiple($this->getMainTable(), $data);
        }
    }

    /**
     * @param string $productId
     * @return array
     */
    private function getOptionTitles($productId) {
        $result = [];
        $connection = $this->getConnection();
        $selectOldOptions     = $connection->select()->from(
            ['option_data' => $this->getTable('catalog_product_option')],
            ['option_id']
        )->joinLeft(
            ['option_title' => $this->getTable('catalog_product_option_title')],
            'option_data.option_id = option_title.option_id',
            'title'
        )->where('option_data.product_id = ?', $productId);

        $data = $connection->fetchAll($selectOldOptions);
        foreach ($data as $row) {
            $result[$row['title']] = $row['option_id'];
        }

        return $result;
    }
}
