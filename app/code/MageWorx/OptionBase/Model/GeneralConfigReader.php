<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See https://www.mageworx.com/terms-and-conditions for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionBase\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class GeneralConfigReader
{
    const XML_PATH_USE_OPTION_VALUE_COLLECTION_CACHE = 'mageworx_apo/general/use_option_value_collection_cache';

    protected ScopeConfigInterface $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function useOptionValueCollectionCache(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_USE_OPTION_VALUE_COLLECTION_CACHE);
    }
}
