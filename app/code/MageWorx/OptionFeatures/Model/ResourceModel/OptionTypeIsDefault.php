<?php
/**
 * Copyright © 2018 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionFeatures\Model\ResourceModel;

use MageWorx\OptionFeatures\Model\OptionTypeIsDefault as IsDefaultModel;

class OptionTypeIsDefault extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize main table and table id field
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(IsDefaultModel::TABLE_NAME, 'option_type_is_default_id');
    }
}
