<?php
/**
 * Copyright © 2017 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionVisibility\Model;

use Magento\Framework\Model\AbstractModel;

class OptionStoreView extends AbstractModel
{
    const TABLE_NAME                 = 'mageworx_optionvisibility_option_store_view';
    const OPTIONTEMPLATES_TABLE_NAME = 'mageworx_optiontemplates_group_option_store_view';

    const COLUMN_NAME_VISIBILITY_STORE_VIEW_ID = 'visibility_store_view_id';
    const COLUMN_NAME_OPTION_ID                = 'option_id';
    const COLUMN_NAME_STORE_ID                 = 'customer_store_id';

    const KEY_STORE_VIEW        = 'store_view';
    const FIELD_OPTION_ID_ALIAS = 'mageworx_store_view_option_id';

    /**
     * Set resource model and Id field name
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('MageWorx\OptionVisibility\Model\ResourceModel\OptionStoreView');
        $this->setIdFieldName('visibility_store_view_id');
    }
}