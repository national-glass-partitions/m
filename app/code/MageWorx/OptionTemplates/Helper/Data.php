<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionTemplates\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const TABLE_NAME_GROUP                   = 'mageworx_optiontemplates_group';
    const TABLE_NAME_GROUP_OPTION            = 'mageworx_optiontemplates_group_option';
    const TABLE_NAME_RELATION                = 'mageworx_optiontemplates_relation';
    const TABLE_NAME_GROUP_OPTION_PRICE      = 'mageworx_optiontemplates_group_option_price';
    const TABLE_NAME_GROUP_OPTION_TITLE      = 'mageworx_optiontemplates_group_option_title';
    const TABLE_NAME_GROUP_OPTION_TYPE_VALUE = 'mageworx_optiontemplates_group_option_type_value';
    const TABLE_NAME_GROUP_OPTION_TYPE_PRICE = 'mageworx_optiontemplates_group_option_type_price';
    const TABLE_NAME_GROUP_OPTION_TYPE_TITLE = 'mageworx_optiontemplates_group_option_type_title';

    const COLUMN_NAME_GROUP_ID       = 'group_id';
    const COLUMN_NAME_TITLE          = 'title';
    const COLUMN_NAME_UPDATED_AT     = 'updated_at';
    const COLUMN_NAME_IS_ACTIVE      = 'is_active';
    const COLUMN_NAME_ID             = 'id';
    const COLUMN_NAME_PRODUCT_ID     = 'product_id';
    const COLUMN_NAME_OPTION_ID      = 'option_id';
    const COLUMN_NAME_TYPE           = 'type';
    const COLUMN_NAME_IS_REQUIRE     = 'is_require';
    const COLUMN_NAME_SKU            = 'sku';
    const COLUMN_NAME_MAX_CHARACTERS = 'max_characters';
    const COLUMN_NAME_FILE_EXTENSION = 'file_extension';
    const COLUMN_NAME_IMAGE_SIZE_X   = 'image_size_x';
    const COLUMN_NAME_IMAGE_SIZE_Y   = 'image_size_y';
    const COLUMN_NAME_SORT_ORDER     = 'sort_order';

    const COLUMN_NAME_OPTION_PRICE_ID      = 'option_price_id';
    const COLUMN_NAME_STORE_ID             = 'store_id';
    const COLUMN_NAME_PRICE                = 'price';
    const COLUMN_NAME_PRICE_TYPE           = 'price_type';
    const COLUMN_NAME_OPTION_TITLE_ID      = 'option_title_id';
    const COLUMN_NAME_OPTION_TYPE_ID       = 'option_type_id';
    const COLUMN_NAME_OPTION_TYPE_PRICE_ID = 'option_type_price_id';
    const COLUMN_NAME_OPTION_TYPE_TITLE_ID = 'option_type_title_id';

    /**
     * Admin config settings
     */
    const XML_REAPPLY_ATTRIBUTE_EXCEPTIONS = 'mageworx_apo/optiontemplates/reapply_attribute_exceptions';

    /**
     * @var \Magento\Framework\Component\ComponentRegistrarInterface
     */
    protected $componentRegistrar;

    /**
     * @var \Magento\Framework\Filesystem\Directory\ReadFactory
     */
    protected $readFactory;

    /**
     * Data constructor.
     *
     * @param Context $context
     * @param \Magento\Framework\Component\ComponentRegistrarInterface $componentRegistrar
     * @param \Magento\Framework\Filesystem\Directory\ReadFactory $readFactory
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Component\ComponentRegistrarInterface $componentRegistrar,
        \Magento\Framework\Filesystem\Directory\ReadFactory $readFactory
    ) {
        parent::__construct($context);
        $this->componentRegistrar = $componentRegistrar;
        $this->readFactory        = $readFactory;
    }

    /**
     * Get attribute keys that will not be overwritten on template reapply
     *
     * @param int $storeId
     * @return bool
     */
    public function getReapplyExceptionAttributeKeys($storeId = null)
    {
        return explode(
            ',',
            $this->scopeConfig->getValue(
                self::XML_REAPPLY_ATTRIBUTE_EXCEPTIONS,
                ScopeInterface::SCOPE_STORE,
                $storeId
            )
        );
    }
}