<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\DynamicOptionsBase\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use MageWorx\DynamicOptionsBase\Api\Data\DynamicOptionsConfigReaderInterface;

class DynamicOptionsConfigReader implements DynamicOptionsConfigReaderInterface
{
    /**
     * XML config path enable dynamic options
     */
    const ENABLE_DYNAMIC_OPTIONS = 'mageworx_dynamic_options/general/enable';

    private ScopeConfigInterface $scopeConfig;
    private ?int $storeId;

    /**
     * DynamicOptionsConfigReader constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return int|null
     */
    public function getStoreId()
    {
        return $this->storeId ?? Store::DEFAULT_STORE_ID;
    }

    /**
     * @param int $storeId
     * @return $this
     */
    public function setStoreId($storeId): DynamicOptionsConfigReaderInterface
    {
        $this->storeId = (int)$storeId;

        return $this;
    }

    /**
     * @param int $storeId
     * @return bool
     */
    public function isEnabled($storeId = null): bool
    {
        $storeId = $storeId !== null ? $storeId : $this->getStoreId();

        return $this->scopeConfig->isSetFlag(
            self::ENABLE_DYNAMIC_OPTIONS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
