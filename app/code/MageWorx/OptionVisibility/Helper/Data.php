<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionVisibility\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    const XML_PATH_ENABLE_VISIBILITY_CUSTOMER_GROUP = 'mageworx_apo/optionvisibility/enable_visibility_customer_group';
    const XML_PATH_ENABLE_VISIBILITY_STORE_VIEW     = 'mageworx_apo/optionvisibility/enable_visibility_store_view';
    const XML_PATH_USE_IS_DISABLE                   = 'mageworx_apo/optionvisibility/use_is_disabled';

    const KEY_DISABLED           = 'disabled';
    const KEY_DISABLED_BY_VALUES = 'disabled_by_values';
    const DISABLED_TRUE          = '1';
    const DISABLED_FALSE         = '0';

    /**
     * @param int $storeId
     * @return bool
     */
    public function isVisibilityCustomerGroupEnabled($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLE_VISIBILITY_CUSTOMER_GROUP,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int $storeId
     * @return bool
     */
    public function isVisibilityStoreViewEnabled($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLE_VISIBILITY_STORE_VIEW,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     *
     * @param int $storeId
     * @return bool
     */
    public function isEnabledIsDisabled($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_USE_IS_DISABLE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}