<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\DynamicOptionsBase\Api\Data;

use Magento\Store\Model\Store;

/**
 * Dynamic Options config reader interface
 */
interface DynamicOptionsConfigReaderInterface
{
    /**
     * @return int|null
     */
    public function getStoreId();

    /**
     * @param int $storeId
     * @return $this
     */
    public function setStoreId($storeId): DynamicOptionsConfigReaderInterface;

    /**
     * @param int $storeId
     * @return bool
     */
    public function isEnabled($storeId = Store::DEFAULT_STORE_ID): bool;
}
