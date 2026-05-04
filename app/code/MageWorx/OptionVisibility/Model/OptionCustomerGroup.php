<?php
/**
 * Copyright © 2017 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionVisibility\Model;

use Magento\Framework\Model\AbstractModel;

class OptionCustomerGroup extends AbstractModel
{
    const TABLE_NAME                 = 'mageworx_optionvisibility_option_customer_group';
    const OPTIONTEMPLATES_TABLE_NAME = 'mageworx_optiontemplates_group_option_customer_group';

    const COLUMN_NAME_VISIBILITY_CUSTOMER_GROUP_ID = 'visibility_customer_group_id';
    const COLUMN_NAME_OPTION_ID                    = 'option_id';
    const COLUMN_NAME_GROUP_ID                     = 'customer_group_id';

    const KEY_CUSTOMER_GROUP    = 'customer_group';
    const FIELD_OPTION_ID_ALIAS = 'mageworx_customer_group_option_id';

    /**
     * Set resource model and Id field name
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('MageWorx\OptionVisibility\Model\ResourceModel\OptionCustomerGroup');
        $this->setIdFieldName('visibility_customer_group_id');
    }
}