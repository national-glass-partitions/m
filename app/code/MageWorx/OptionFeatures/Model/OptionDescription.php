<?php
/**
 * Copyright © 2017 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionFeatures\Model;

use Magento\Framework\Model\AbstractModel;

class OptionDescription extends AbstractModel
{
    const TABLE_NAME                 = 'mageworx_optionfeatures_option_description';
    const OPTIONTEMPLATES_TABLE_NAME = 'mageworx_optiontemplates_group_option_description';

    const COLUMN_NAME_OPTION_DESCRIPTION_ID = 'option_description_id';
    const COLUMN_NAME_OPTION_ID             = 'option_id';
    const COLUMN_NAME_STORE_ID              = 'store_id';
    const COLUMN_NAME_DESCRIPTION           = 'description';

    const FIELD_OPTION_ID_ALIAS = 'mageworx_description_option_id';

    /**
     * Set resource model and Id field name
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('MageWorx\OptionFeatures\Model\ResourceModel\OptionDescription');
        $this->setIdFieldName(self::COLUMN_NAME_OPTION_DESCRIPTION_ID);
    }
}
