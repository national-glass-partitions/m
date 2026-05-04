<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionAdvancedPricing\Plugin;

use Magento\Catalog\Model\Config\Source\ProductPriceOptionsInterface;
use \Magento\Store\Model\StoreManagerInterface;
use MageWorx\OptionAdvancedPricing\Helper\Data as Helper;

class AddPriceTypePlugin
{
    private StoreManagerInterface $storeManager;

    public function __construct(StoreManagerInterface $storeManager)
    {
        $this->storeManager = $storeManager;
    }

    /**
     * @param ProductPriceOptionsInterface $subject
     * @param array $result
     * @return array[]
     */
    public function afterToOptionArray(ProductPriceOptionsInterface $subject, array $result)
    {
        array_push(
            $result,
            ['value' =>  Helper::PRICE_TYPE_PER_CHARACTER, 'label' => __('Fixed per character')]
        );

        return $result;
    }

    /**
     * @param ProductPriceOptionsInterface $subject
     * @param array $result
     * @return array
     */
    public function afterPrefixesToOptionArray(ProductPriceOptionsInterface $subject, array $result)
    {
        array_push(
            $result,
            ['value' => Helper::PRICE_TYPE_PER_CHARACTER, 'label' => $this->getCurrencySymbol()]
        );

        return $result;
    }

    /**
     * @return string
     */
    private function getCurrencySymbol()
    {
        $store = $this->storeManager->getStore();

        return $store->getBaseCurrency()->getCurrencySymbol();
    }

}
