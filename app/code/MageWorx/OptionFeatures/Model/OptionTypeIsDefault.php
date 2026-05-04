<?php
/**
 * Copyright © 2018 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionFeatures\Model;

use Magento\Catalog\Model\Product;
use Magento\Framework\Model\AbstractExtensibleModel;

class OptionTypeIsDefault extends AbstractExtensibleModel
{
    const TABLE_NAME                 = 'mageworx_optionfeatures_option_type_is_default';
    const OPTIONTEMPLATES_TABLE_NAME = 'mageworx_optiontemplates_group_option_type_is_default';

    const COLUMN_NAME_OPTION_TYPE_IS_DEFAULT_ID = 'option_type_is_default_id';
    const COLUMN_NAME_OPTION_TYPE_ID            = 'option_type_id';
    const COLUMN_NAME_STORE_ID                  = 'store_id';
    const COLUMN_NAME_IS_DEFAULT                = 'is_default';

    /**
     * Set resource model and Id field name
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('MageWorx\OptionFeatures\Model\ResourceModel\OptionTypeIsDefault');
        $this->setIdFieldName(self::COLUMN_NAME_OPTION_TYPE_IS_DEFAULT_ID);
    }
}
