<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionSkuPolicy\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    const KEY_SKU_POLICY = 'sku_policy';

    const SKU_POLICY_USE_CONFIG  = 'use_config';
    const SKU_POLICY_STANDARD    = 'standard';
    const SKU_POLICY_DISABLED    = 'disabled';
    const SKU_POLICY_REPLACEMENT = 'replacement';
    const SKU_POLICY_INDEPENDENT = 'independent';
    const SKU_POLICY_GROUPED     = 'grouped';

    const XML_PATH_ENABLE_SKU_POLICY   = 'mageworx_apo/optionskupolicy/enable_sku_policy';
    const XML_PATH_DEFAULT_SKU_POLICY  = 'mageworx_apo/optionskupolicy/default_sku_policy';
    const XML_PATH_APPLY_SKU_POLICY_TO = 'mageworx_apo/optionskupolicy/apply_sku_policy_to';
    const XML_PATH_SPLIT_INDEPENDENTS  = 'mageworx_apo/optionskupolicy/split_independents';

    /**
     * Check if SKU Policy feature enabled
     *
     * @param int|string|\Magento\Store\Model\Store $store
     * @return bool
     */
    public function isEnabledSkuPolicy($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLE_SKU_POLICY,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get default SKU Policy
     *
     * @param int|string|\Magento\Store\Model\Store $store
     * @return string
     */
    public function getDefaultSkuPolicy($store = null)
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_DEFAULT_SKU_POLICY,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Check if SKU policy is applied to cart and order, if not - to order only
     *
     * @param int|string|\Magento\Store\Model\Store $store
     * @return bool
     */
    public function isSkuPolicyAppliedToCartAndOrder($store = null)
    {
        return !$this->scopeConfig->isSetFlag(
            self::XML_PATH_APPLY_SKU_POLICY_TO,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Split independent option-products if they do not belong to the same parent product
     *
     * @param int|string|\Magento\Store\Model\Store $store
     * @return bool
     */
    public function isSplitIndependents($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_SPLIT_INDEPENDENTS,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }
}
