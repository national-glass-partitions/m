<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionBase\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\State;
use MageWorx\OptionBase\Helper\System as SystemHelper;

class CustomerVisibility extends AbstractHelper
{
    private State $state;
    protected Http $request;
    protected System $systemHelper;

    /**
     * CustomerVisibility constructor.
     *
     * @param State $state
     * @param Context $context
     * @param Http $request
     * @param SystemHelper $systemHelper
     */
    public function __construct(
        State $state,
        Context $context,
        SystemHelper $systemHelper,
        Http $request
    ) {
        $this->state           = $state;
        $this->request         = $request;
        $this->systemHelper    = $systemHelper;

        parent::__construct($context);
    }

    /**
     * Get current customer group ID
     *
     * @return int
     */
    public function getCurrentCustomerGroupId()
    {
        return $this->systemHelper->resolveCurrentCustomerGroupId();
    }

    /**
     * @return int
     */
    public function getCurrentCustomerStoreId()
    {
        return $this->systemHelper->resolveCurrentStoreId();
    }

    /**
     * Get Customer Group ID for ALL group
     *
     * @return int
     */
    public function getAllCustomersGroupId()
    {
        return GroupInterface::CUST_GROUP_ALL;
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isAreaFrontend()
    {
        return $this->state->getAreaCode() == 'frontend' || $this->state->getAreaCode() == 'webapi_rest';
    }

    /**
     * @return bool
     */
    public function isOrderCreate()
    {
        return $this->request->getControllerName() == 'order_create'
            || $this->request->getFullActionName() == 'mageworx_optionbase_config_get';
    }

    /**
     * @return bool
     */
    public function isVisibilityFilterRequired()
    {
        return $this->isAreaFrontend() || $this->isOrderCreate();
    }
}
