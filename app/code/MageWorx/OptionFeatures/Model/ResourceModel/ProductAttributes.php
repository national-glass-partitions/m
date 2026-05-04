<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionFeatures\Model\ResourceModel;

class ProductAttributes extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * Initialize main table and table id field
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\MageWorx\OptionFeatures\Model\ProductAttributes::TABLE_NAME, 'entity_id');
    }
}
