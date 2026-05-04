<?php
/**
 * Copyright © 2017 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionDependency\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    // Option attributes
    const KEY_OPTION_TITLE_ID             = 'option_title_id';
    const KEY_OPTION_DEPENDENCY_TYPE      = 'dependency_type';
    // Option value attributes
    const KEY_OPTION_TYPE_TITLE_ID        = 'option_type_title_id';
    const KEY_OPTION_TYPE_DEPENDENCY_TYPE = 'dependency_type';

    const XML_PATH_USE_TITLE_ID    = 'mageworx_apo/optiondependency/use_title_id';

    /**
     * Check if 'use title id' is enabled
     *
     * @param int $storeId
     * @return bool
     */
    public function isTitleIdEnabled($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_USE_TITLE_ID,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
