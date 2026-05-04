<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionBase\Model;

class OptionTitle
{
    const TABLE_NAME                 = 'catalog_product_option_title';
    const OPTIONTEMPLATES_TABLE_NAME = 'mageworx_optiontemplates_group_option_title';

    const FIELD_OPTION_TITLE_ID   = 'option_title_id';
    const FIELD_STORE_ID          = 'store_id';
    const FIELD_TITLE             = 'title';
    const FIELD_OPTION_ID         = 'option_id';
    const FIELD_OPTION_ID_ALIAS   = 'magento_title_option_id';

    const KEY_MAGEWORX_OPTION_TITLE = 'mageworx_title';
}
