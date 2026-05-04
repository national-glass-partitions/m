<?php
/**
 * Copyright © 2017 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionFeatures\Model\ResourceModel;

class OptionTypeDescription extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize main table and table id field
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\MageWorx\OptionFeatures\Model\OptionTypeDescription::TABLE_NAME, 'option_type_description_id');
    }
}
