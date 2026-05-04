<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionAdvancedPricing\Model;

use Magento\Catalog\Model\Product\Option\Value as OptionValue;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Serialize\Serializer\Json as Serializer;
use MageWorx\OptionAdvancedPricing\Api\TierPriceStorageInterface;
use MageWorx\OptionAdvancedPricing\Helper\Data as Helper;
use MageWorx\OptionAdvancedPricing\Model\TierPrice as TierPriceModel;
use MageWorx\OptionBase\Helper\CustomerVisibility as CustomerVisibilityHelper;
use MageWorx\OptionBase\Helper\Price as BasePriceHelper;
use MageWorx\OptionAdvancedPricing\Model\SpecialPrice as SpecialPriceModel;
use Magento\Framework\Pricing\PriceCurrencyInterface;

class TierPrice extends AbstractModel
{
    const TABLE_NAME                 = 'mageworx_optionadvancedpricing_option_type_tier_price';
    const OPTIONTEMPLATES_TABLE_NAME = 'mageworx_optiontemplates_group_option_type_tier_price';

    const COLUMN_OPTION_TYPE_TIER_PRICE_ID = 'option_type_tier_id';
    const COLUMN_OPTION_TYPE_ID            = 'option_type_id';
    const COLUMN_CUSTOMER_GROUP_ID         = 'customer_group_id';
    const COLUMN_QTY                       = 'qty';
    const COLUMN_PRICE                     = 'price';
    const COLUMN_PRICE_TYPE                = 'price_type';
    const COLUMN_DATE_FROM                 = 'date_from';
    const COLUMN_DATE_TO                   = 'date_to';

    const FIELD_OPTION_TYPE_ID_ALIAS = 'mageworx_tier_price_option_type_id';
    const KEY_TIER_PRICE             = 'tier_price';

    protected CustomerVisibilityHelper $customerVisibilityHelper;
    protected Helper $helper;
    protected BasePriceHelper $basePriceHelper;
    protected SpecialPrice $specialPriceModel;
    protected ConditionValidator $conditionValidator;
    protected PriceCurrencyInterface $priceCurrency;
    protected Serializer $serializer;
    protected TierPriceStorageInterface $tierPriceStorage;

    public function __construct(
        SpecialPriceModel $specialPriceModel,
        Helper $helper,
        BasePriceHelper $basePriceHelper,
        ConditionValidator $conditionValidator,
        CustomerVisibilityHelper $customerVisibilityHelper,
        Context $context,
        Registry $registry,
        PriceCurrencyInterface $priceCurrency,
        Serializer $serializer,
        TierPriceStorageInterface $tierPriceStorage,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->specialPriceModel        = $specialPriceModel;
        $this->customerVisibilityHelper = $customerVisibilityHelper;
        $this->helper                   = $helper;
        $this->basePriceHelper          = $basePriceHelper;
        $this->conditionValidator       = $conditionValidator;
        $this->priceCurrency            = $priceCurrency;
        $this->serializer               = $serializer;
        $this->tierPriceStorage         = $tierPriceStorage;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Set resource model and Id field name
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('MageWorx\OptionAdvancedPricing\Model\ResourceModel\TierPrice');
        $this->setIdFieldName(self::COLUMN_OPTION_TYPE_TIER_PRICE_ID);
    }

    /**
     * Get tier prices suitable by date and customer group
     *
     * @param OptionValue $optionValue
     * @param bool $isNeedConvert
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getSuitableTierPrices(OptionValue $optionValue, $isNeedConvert = false)
    {
        $preparedData   = [];
        // Trying to get tier price data directly from the value object
        $tierPricesJson = $optionValue->getData(static::KEY_TIER_PRICE);

        // If no data in value object retrieve it from the storage
        if (empty($tierPricesJson) && $optionValue->getProduct()) {
            $tierPricesJson = $this->tierPriceStorage->getTierPriceData($optionValue->getProduct(), $optionValue);
            $optionValue->setData(TierPriceModel::KEY_TIER_PRICE, $tierPricesJson);
        }

        // If no data in value object and storage return the empty array
        if (!$tierPricesJson) {
            return $preparedData;
        }

        $tierPrices = $this->serializer->unserialize($tierPricesJson);
        if (!$tierPrices) {
            return $preparedData;
        }

        $actualSpecialPrice = $this->specialPriceModel->getActualSpecialPrice($optionValue);
        if (!is_null($actualSpecialPrice) && $actualSpecialPrice < $optionValue->getPrice(true)) {
            $actualPrice = $actualSpecialPrice;
        } else {
            $actualPrice = $optionValue->getPrice(true);
        }

        $currentCustomer = (int)$this->customerVisibilityHelper->getCurrentCustomerGroupId();
        foreach ($tierPrices as $tierPriceItem) {
            if ($tierPriceItem['price_type'] == Helper::PRICE_TYPE_PERCENTAGE_DISCOUNT) {
                $tierPriceItem['price']      = $this->helper->getCalculatedPriceWithPercentageDiscount(
                    $optionValue,
                    $tierPriceItem
                );
                $tierPriceItem['price_type'] = Helper::PRICE_TYPE_FIXED;
            }

            $tierPriceItem['price_incl_tax'] = $this->basePriceHelper->getTaxPrice(
                $optionValue->getOption()->getProduct(),
                $tierPriceItem['price'],
                true
            );

            if ($isNeedConvert) {
                $tierPriceItem['price']          = $this->priceCurrency->convert($tierPriceItem['price']);
                $tierPriceItem['price_incl_tax'] = $this->priceCurrency->convert($tierPriceItem['price_incl_tax']);
                $actualPrice                     = $this->priceCurrency->convert($actualPrice);
            }

            if (!$this->conditionValidator->isValidated($tierPriceItem, $actualPrice)) {
                continue;
            }

            $tierPriceItem['percent'] = 100 - round($tierPriceItem['price'] / $actualPrice * 100);
            if ($this->isValidCustomerGroup((int)$tierPriceItem['customer_group_id'], $currentCustomer) &&
                empty($preparedData[(string)$tierPriceItem['qty']])
            ) {

                /**
                 * Without specifying the type we get Implicit conversion of a float number to an integer number
                 * Eg. 0.5 -> 0, 1.3 -> 1
                 */
                $preparedData[(string)$tierPriceItem['qty']] = $tierPriceItem;
            }

        }
        return $preparedData;
    }

    /**
     * Validate customer group
     *
     * @param int $customerGroupId
     * @param int $currentCustomer
     * @return bool
     */
    protected function isValidCustomerGroup(int $customerGroupId, int $currentCustomer): bool
    {
        return $customerGroupId == $currentCustomer ||
            $customerGroupId == $this->customerVisibilityHelper->getAllCustomersGroupId();
    }
}
