<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionInventory\Plugin;

use Magento\Store\Model\ScopeInterface;


class TransferSetting
{
    protected \MageWorx\OptionInventory\Helper\Data $helperData;

    /**
     * TransferSetting constructor.
     *
     * @param \MageWorx\OptionInventory\Helper\Data $helperData
     */
    public function __construct(
        \MageWorx\OptionInventory\Helper\Data $helperData
    ) {
        $this->helperData = $helperData;
    }

    /**
     * @param \MageWorx\OptionBase\Helper\Data $subject
     * @param bool $result
     * @param null|int $storeId
     * @return bool
     */
    public function afterIsHiddenOutOfStockOptions(\MageWorx\OptionBase\Helper\Data $subject, $result, $storeId = null)
    {
        if (!$this->helperData->isEnabledOptionInventory()) {
            return false;
        }

        return !$this->helperData->isDisplayOutOfStockOptions($storeId);
    }

    /**
     * @param \MageWorx\OptionBase\Helper\Data $subject
     * @param bool $result
     * @param null $storeId
     * @return bool
     */
    public function afterIsDisabledOutOfStockOptions(\MageWorx\OptionBase\Helper\Data $subject, $result, $storeId = null)
    {
        return $this->helperData->isDisplayOutOfStockOptions($storeId);
    }
}
