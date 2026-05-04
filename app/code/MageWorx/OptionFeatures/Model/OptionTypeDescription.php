<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionFeatures\Model;

use Magento\Catalog\Model\Product;
use Magento\Framework\Model\AbstractExtensibleModel;

class OptionTypeDescription extends AbstractExtensibleModel
{
    const TABLE_NAME                 = 'mageworx_optionfeatures_option_type_description';
    const OPTIONTEMPLATES_TABLE_NAME = 'mageworx_optiontemplates_group_option_type_description';

    const COLUMN_NAME_OPTION_TYPE_DESCRIPTION_ID = 'option_type_description_id';
    const COLUMN_NAME_OPTION_TYPE_ID             = 'option_type_id';
    const COLUMN_NAME_STORE_ID                   = 'store_id';
    const COLUMN_NAME_DESCRIPTION                = 'description';

    const FIELD_OPTION_TYPE_ID_ALIAS = 'mageworx_description_option_type_id';

    /**
     * Set resource model and Id field name
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('MageWorx\OptionFeatures\Model\ResourceModel\OptionTypeDescription');
        $this->setIdFieldName(self::COLUMN_NAME_OPTION_TYPE_DESCRIPTION_ID);
    }
}
