<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types = 1);

namespace MageWorx\OptionAdvancedPricing\Plugin;

use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use MageWorx\OptionAdvancedPricing\Helper\Data as Helper;
use MageWorx\OptionAdvancedPricing\Model\SpecialPrice as SpecialPriceModel;
use MageWorx\OptionAdvancedPricing\Model\TierPrice as TierPriceModel;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use MageWorx\OptionBase\Helper\Price as BasePriceHelper;
use Magento\Framework\Json\DecoderInterface;
use MageWorx\OptionBase\Plugin\ExtendPriceConfig;
use Magento\Catalog\Model\Product\Option;
use Magento\Catalog\Model\Product\Option\Value;
use Magento\Catalog\Model\Product;
use MageWorx\OptionAdvancedPricing\Api\TierPriceStorageInterface;
use MageWorx\OptionAdvancedPricing\Api\SpecialPriceStorageInterface;

class AfterExtendPriceConfig
{
    protected Helper                       $helper;
    protected SpecialPriceModel            $specialPriceModel;
    protected TierPriceModel               $tierPriceModel;
    protected PriceCurrencyInterface       $priceCurrency;
    protected BasePriceHelper              $basePriceHelper;
    protected State                        $state;
    protected DecoderInterface             $jsonDecoder;
    protected SpecialPriceStorageInterface $specialPriceStorage;
    protected TierPriceStorageInterface    $tierPriceStorage;

    public function __construct(
        Helper                       $helper,
        SpecialPriceModel            $specialPriceModel,
        TierPriceModel               $tierPriceModel,
        PriceCurrencyInterface       $priceCurrency,
        BasePriceHelper              $basePriceHelper,
        State                        $state,
        SpecialPriceStorageInterface $specialPriceStorage,
        TierPriceStorageInterface    $tierPriceStorage,
        array                        $data = []
    ) {
        $this->helper              = $helper;
        $this->specialPriceModel   = $specialPriceModel;
        $this->tierPriceModel      = $tierPriceModel;
        $this->priceCurrency       = $priceCurrency;
        $this->basePriceHelper     = $basePriceHelper;
        $this->state               = $state;
        $this->specialPriceStorage = $specialPriceStorage;
        $this->tierPriceStorage    = $tierPriceStorage;
    }

    /**
     * Get Extended option value json config
     *
     * @param ExtendPriceConfig $subject
     * @param array $result
     * @param array $defaultConfig
     * @param Option $option
     * @param int $valueId
     * @param Value $value
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function afterGetExtendedOptionValueJsonConfig(
        ExtendPriceConfig $subject,
        array             $result,
        array             $defaultConfig,
        Option            $option,
        int               $valueId,
        Value             $value
    ): array {
        $product = $subject->getProduct();

        if ($this->helper->isSpecialPriceEnabled()) {
            $result = $this->getSpecialPriceDataToResult($product, $result, $value);
        }

        if ($this->helper->isTierPriceEnabled() && 'graphql' === $this->state->getAreaCode()) {
            $result['tier_price_display_data'] = $this->getTierPriceDataToResult($product, $value);
        }

        return $result;
    }

    /**
     * Get special price data for option value json config
     *
     * @param Product $product
     * @param array $result
     * @param Value $value
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function getSpecialPriceDataToResult(Product $product, array $result, Value $value): array
    {
        $storedSpecialPriceJson = $this->specialPriceStorage->getSpecialPriceData($product, $value);
        $value->setData(SpecialPriceModel::KEY_SPECIAL_PRICE, $storedSpecialPriceJson);

        $specialPrice   = $this->specialPriceModel->getActualSpecialPrice($value, true);
        $needIncludeTax = $this->basePriceHelper->getCatalogPriceContainsTax($product->getStoreId());
        $isSpecialPrice = false;

        if ($specialPrice !== null) {
            $basePriceAmount  = $result['prices']['basePrice']['amount'];
            $finalPriceAmount = $result['prices']['finalPrice']['amount'];
            if ($needIncludeTax) {
                $basePriceAmount = min(
                    $basePriceAmount,
                    $specialPrice * ($basePriceAmount / $finalPriceAmount)
                );
            } else {
                $basePriceAmount = min($basePriceAmount, $specialPrice);
            }
            $finalPriceAmount = min($finalPriceAmount, $specialPrice);

            if ($specialPrice <= $finalPriceAmount) {
                $isSpecialPrice = true;
            }

            $basePriceAmount  = $this->basePriceHelper->getTaxPrice(
                $product,
                $basePriceAmount,
                $needIncludeTax
            );
            $finalPriceAmount = $this->basePriceHelper->getTaxPrice(
                $product,
                $finalPriceAmount,
                $needIncludeTax || $isSpecialPrice
            );

            $result['prices']['basePrice']['amount']  = $basePriceAmount;
            $result['prices']['finalPrice']['amount'] = $finalPriceAmount;

            $result['special_price_display_node'] = $this->helper->getSpecialPriceDisplayNode(
                $result['prices'],
                $this->specialPriceModel->getSpecialPriceItem()
            );
        }

        return $result;
    }

    /**
     * Get special price data for option value json config
     *
     * @param Product $product
     * @param Value $value
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function getTierPriceDataToResult(Product $product, Value $value): array
    {
        $storedTierPriceJson = $this->tierPriceStorage->getTierPriceData($product, $value);
        $value->setData(TierPriceModel::KEY_TIER_PRICE, $storedTierPriceJson);

        return $this->tierPriceModel->getSuitableTierPrices($value, true);
    }
}
