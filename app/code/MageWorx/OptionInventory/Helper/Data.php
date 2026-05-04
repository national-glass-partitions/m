<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionInventory\Helper;

use Magento\Store\Model\ScopeInterface;

/**
 * OptionInventory Data Helper.
 *
 * @package MageWorx\OptionInventory\Helper
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const KEY_QTY          = 'qty';
    const KEY_MANAGE_STOCK = 'manage_stock';

    /**
     * XML config path enable functionality
     */
    const XML_PATH_ENABLE_OPTION_INVENTORY = 'mageworx_apo/optioninventory/enable';

    /**
     * XML config path show option qty on frontend
     */
    const XML_PATH_DISPLAY_OPTION_INVENTORY_ON_FRONTEND =
        'mageworx_apo/optioninventory/display_option_inventory_on_frontend';

    /**
     * XML config path show out of stock options
     */
    const XML_PATH_DISABLE_OR_HIDE_OUT_OF_STOCK_OPTIONS = 'mageworx_apo/optioninventory/disable_out_of_stock_options';

    /**
     * XML config path require hidden out of stock options
     */
    const XML_PATH_REQUIRE_HIDDEN_OUT_OF_STOCK_OPTIONS = 'mageworx_apo/optioninventory/require_hidden_out_of_stock_options';

    /**
     * XML config path show out of stock message
     */
    const XML_PATH_DISPLAY_OUT_OF_STOCK_MESSAGE = 'mageworx_apo/optioninventory/display_out_of_stock_message';

    /**
     * XML config path show out of stock message on options level
     */
    const XML_PATH_DISPLAY_OUT_OF_STOCK_MESSAGE_ON_OPTIONS_LEVEL = 'mageworx_apo/optioninventory/display_out_of_stock_message_on_options_level';


    /**
     * Check if enabled
     *
     * @param int $storeId
     * @return bool
     */
    public function isEnabledOptionInventory(): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ENABLE_OPTION_INVENTORY
        );
    }

    /**
     * Check if 'show option qty on frontend' are enable
     *
     * @param int $storeId
     * @return bool
     */
    public function isDisplayOptionInventoryOnFrontend($storeId = null): bool
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_DISPLAY_OPTION_INVENTORY_ON_FRONTEND,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if 'show out of stock options' are enable
     * If true - out of stock options are disable
     * If false - out of stock options are hide
     *
     * @param int $storeId
     * @return bool
     */
    public function isDisplayOutOfStockOptions($storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_DISABLE_OR_HIDE_OUT_OF_STOCK_OPTIONS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if 'show out of stock message' are enable
     *
     * @param int $storeId
     * @return bool
     */
    public function isDisplayOutOfStockMessage($storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_DISPLAY_OUT_OF_STOCK_MESSAGE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if 'show out of stock message on options level' are enable
     *
     * @param int $storeId
     * @return bool
     */
    public function isDisplayOutOfStockMessageOnOptionsLevel($storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_DISPLAY_OUT_OF_STOCK_MESSAGE_ON_OPTIONS_LEVEL,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Require hidden out of stock option
     *
     * @param int $storeId
     * @return bool
     */
    public function isRequireHiddenOutOfStockOptions($storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_REQUIRE_HIDDEN_OUT_OF_STOCK_OPTIONS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
