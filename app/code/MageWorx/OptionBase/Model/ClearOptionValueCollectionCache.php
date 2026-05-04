<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See https://www.mageworx.com/terms-and-conditions for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionBase\Model;

use Magento\Framework\App\Cache\Type\Collection;
use MageWorx\OptionBase\Model\ResourceModel\Product\Option\Value\Collection\CacheFetchStrategy;
use Zend_Cache;

class ClearOptionValueCollectionCache
{
    protected Collection $cache;

    public function __construct(Collection $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param array $productIds - if empty - all data will be cleaned
     */
    public function execute(array $productIds = []): void
    {
        if ($productIds) {
            $tags = [];

            foreach ($productIds as $productId) {
                $tags[] = CacheFetchStrategy::CACHE_TAG_PREFIX . '_' . $productId;
            }
        } else {
            $tags = [CacheFetchStrategy::CACHE_TAG_PREFIX];
        }

        $this->cache->clean(Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, $tags);
    }
}
