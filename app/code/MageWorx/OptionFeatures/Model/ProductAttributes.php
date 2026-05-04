<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionFeatures\Model;

use Magento\Catalog\Model\Product;
use Magento\Framework\Model\AbstractExtensibleModel;

class ProductAttributes extends AbstractExtensibleModel
{
    const TABLE_NAME = 'mageworx_optionfeatures_product_attributes';
    const OPTIONTEMPLATES_TABLE_NAME = 'mageworx_optiontemplates_group';

    const COLUMN_ENTITY_ID = 'entity_id';
    const COLUMN_PRODUCT_ID = 'product_id';

    const TRANSFERABLE_DATA = [
        'absolute_cost',
        'absolute_weight',
        'absolute_price'
    ];

    /**
     * Set resource model and Id field name
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('MageWorx\OptionFeatures\Model\ResourceModel\ProductAttributes');
        $this->setIdFieldName('entity_id');
    }

    /**
     * Get data fields available to transfer
     *
     * @return array
     */
    public function getTransferableData()
    {
        $result = [];
        foreach (self::TRANSFERABLE_DATA as $dataKey) {
            $result[$dataKey] = $this->getData($dataKey);
        }

        return $result;
    }

    /**
     * Get data available to transfer from the corresponding product to the ProductAttributes item
     *
     * @param Product $product
     * @return array
     */
    public function getTransferableDataFromProduct(Product $product)
    {
        $result = [];
        foreach (self::TRANSFERABLE_DATA as $dataKey) {
            $result[$dataKey] = $product->getData($dataKey);
        }

        return $result;
    }
}
