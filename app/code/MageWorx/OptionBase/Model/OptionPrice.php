<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionBase\Model;

class OptionPrice
{
    const TABLE_NAME                 = 'catalog_product_option_price';
    const OPTIONTEMPLATES_TABLE_NAME = 'mageworx_optiontemplates_group_option_price';

    const FIELD_OPTION_PRICE_ID = 'option_price_id';
    const FIELD_STORE_ID        = 'store_id';
    const FIELD_PRICE           = 'price';
    const FIELD_PRICE_TYPE      = 'price_type';
    const FIELD_OPTION_ID       = 'option_id';
    const FIELD_OPTION_ID_ALIAS = 'magento_price_option_id';

    const KEY_MAGEWORX_OPTION_PRICE = 'mageworx_option_price';
}
