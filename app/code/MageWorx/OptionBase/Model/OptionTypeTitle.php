<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionBase\Model;

class OptionTypeTitle
{
    const TABLE_NAME                 = 'catalog_product_option_type_title';
    const OPTIONTEMPLATES_TABLE_NAME = 'mageworx_optiontemplates_group_option_type_title';

    const FIELD_OPTION_TYPE_TITLE_ID = 'option_type_title_id';
    const FIELD_STORE_ID             = 'store_id';
    const FIELD_TITLE                = 'title';
    const FIELD_OPTION_TYPE_ID       = 'option_type_id';
    const FIELD_OPTION_TYPE_ID_ALIAS = 'magento_title_option_type_id';

    const KEY_MAGEWORX_OPTION_TYPE_TITLE = 'mageworx_title';
}
