<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionVisibility\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class OptionStoreView extends AbstractDb
{

    /**
     * Initialize main table and table id field
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\MageWorx\OptionVisibility\Model\OptionStoreView::TABLE_NAME, 'visibility_store_view_id');
    }
}