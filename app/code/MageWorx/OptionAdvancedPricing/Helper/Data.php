<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionAdvancedPricing\Helper;

use Magento\Catalog\Model\Product\Option\Value as ProductOptionValue;
use Magento\Catalog\Pricing\Price\BasePrice;
use Magento\Store\Model\ScopeInterface;
use MageWorx\OptionBase\Helper\Price as BasePriceHelper;
use Magento\Framework\Pricing\PriceCurrencyInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_ENABLE_SPECIAL_PRICE     = 'mageworx_apo/option_advanced_pricing/enable_special_price';
    const XML_PATH_ENABLE_TIER_PRICE        = 'mageworx_apo/option_advanced_pricing/enable_tier_price';
    const XML_PATH_DISPLAY_TIER_PRICE_TABLE = 'mageworx_apo/option_advanced_pricing/display_tier_price_table';

    const XML_PATH_OPTION_SPECIAL_PRICE_DISPLAY_TEMPLATE               =
        'mageworx_apo/option_advanced_pricing/option_special_price_display_template';

    const PRICE_TYPE_FIXED               = 'fixed';
    const PRICE_TYPE_PERCENTAGE_DISCOUNT = 'percentage_discount';
    const PRICE_TYPE_PER_CHARACTER       = 'char';

    protected BasePriceHelper $basePriceHelper;
    protected PriceCurrencyInterface $priceCurrency;
    protected string $template;

    public function __construct(
        BasePriceHelper $basePriceHelper,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->basePriceHelper = $basePriceHelper;
        $this->priceCurrency   = $priceCurrency;
        parent::__construct($context);
    }

    /**
     * Check if special price is enabled
     *
     * @param int $storeId
     * @return bool
     */
    public function isSpecialPriceEnabled($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLE_SPECIAL_PRICE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get option special price display template for product page
     *
     * @param int $storeId
     * @return string
     */
    public function getOptionSpecialPriceDisplayTemplate($storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_OPTION_SPECIAL_PRICE_DISPLAY_TEMPLATE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if tier price is enabled
     *
     * @param int $storeId
     * @return bool
     */
    public function isTierPriceEnabled($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLE_TIER_PRICE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if it is needed to display tier price table
     *
     * @param int $storeId
     * @return bool
     */
    public function isDisplayTierPriceTableNeeded($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_DISPLAY_TIER_PRICE_TABLE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get сalculated percentage discount
     *
     * @param \Magento\Catalog\Model\Product\Option\Value $optionValue
     * @param array $priceItem
     * @return float
     */
    public function getCalculatedPriceWithPercentageDiscount($optionValue, $priceItem)
    {
        if ($priceItem['price_type'] == static::PRICE_TYPE_FIXED) {
            return $priceItem['price'];
        }
        return $this->getPrice($optionValue, true) < 0
            ? round((100 + $priceItem['price']) * $this->getPrice($optionValue, true) / 100, 2)
            : round((100 - $priceItem['price']) * $this->getPrice($optionValue, true) / 100, 2);
    }

    /**
     * Return price. If $flag is true and price is percent return converted percent to price
     *
     * @param ProductOptionValue $optionValue
     * @param bool $flag
     * @return float|int
     */
    public function getPrice($optionValue, $flag = false)
    {
        if ($flag) {
            if ($optionValue->getPriceType() === ProductOptionValue::TYPE_PERCENT) {
                $basePrice = $optionValue->getOption()
                                         ->getProduct()
                                         ->getPriceInfo()
                                         ->getPrice(BasePrice::PRICE_CODE)
                                         ->getValue();
                $price     = $basePrice * ($optionValue->getData(ProductOptionValue::KEY_PRICE) / 100);
                return $price;
            }
        }
        return $optionValue->getData(ProductOptionValue::KEY_PRICE);
    }

    /**
     * Get special price node according to option special price display template
     *
     * @param array $priceConfig
     * @param array $priceItem
     * @return string
     */
    public function getSpecialPriceDisplayNode($priceConfig, $priceItem): string
    {
        $this->template = $this->getOptionSpecialPriceDisplayTemplate();

        $specialPriceExclTax = $priceConfig['basePrice']['amount'];
        $specialPriceInclTax = $priceConfig['finalPrice']['amount'];
        $oldPriceExclTax     = $priceConfig['oldPrice']['amount_excl_tax'];
        $oldPriceInclTax     = $priceConfig['oldPrice']['amount_incl_tax'];

        $formattedSpecialPriceInclTax = $this->priceCurrency->format($specialPriceInclTax, false);
        if (strpos($formattedSpecialPriceInclTax, '-') !== false) {
            $this->replaceTemplateNodeItem('+', '');
        }
        $this->replaceTemplateNodeItem(
            '{special_price}',
            $formattedSpecialPriceInclTax
        );
        $this->replaceTemplateNodeItem(
            '{special_price_excl_tax}',
            $this->priceCurrency->format($specialPriceExclTax, false)
        );
        $this->replaceTemplateNodeItem(
            '{price_excl_tax}',
            $this->priceCurrency->format($oldPriceExclTax, false)
        );
        $this->replaceTemplateNodeItem(
            '{price}',
            $this->priceCurrency->format($oldPriceInclTax, false)
        );

        $comment = !empty($priceItem['comment']) ? htmlspecialchars_decode($priceItem['comment']) : '';
        $this->replaceTemplateNodeItem('{special_price_comment}', $comment);

        return (string)$this->template;
    }

    /**
     * Replacer for template node item
     *
     * @param string $search
     * @param float|int $value
     * @return void
     */
    protected function replaceTemplateNodeItem($search, $value)
    {
        $this->template = str_replace($search, $value, $this->template);
    }
}
