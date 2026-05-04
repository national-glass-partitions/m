<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionBase\Model\Config;

use Magento\Catalog\Helper\Data;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Option;
use Magento\Catalog\Model\Product\Option\Value;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Locale\Format;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\App\RequestInterface;
use MageWorx\OptionBase\Model\Product\Option\Attributes as OptionAttributes;
use MageWorx\OptionBase\Model\Product\Option\Value\Attributes as OptionValueAttributes;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionBase\Helper\Price as BasePriceHelper;
use Magento\Tax\Helper\Data as TaxData;
use MageWorx\OptionBase\Model\ResourceModel\CollectionUpdaterRegistry;

class Base
{
    protected DataObjectFactory                      $dataObjectFactory;
    protected Format                                 $localeFormat;
    protected PriceCurrencyInterface                 $priceCurrency;
    protected OptionAttributes                       $optionAttributes;
    protected OptionValueAttributes                  $optionValueAttributes;
    protected BaseHelper                             $baseHelper;
    protected BasePriceHelper                        $basePriceHelper;
    protected RequestInterface                       $request;
    protected \Magento\Framework\Pricing\Helper\Data $pricingHelper;
    protected Data                                   $catalogData;
    protected ManagerInterface                       $eventManager;
    protected TaxData                                $taxData;
    protected CollectionUpdaterRegistry              $collectionUpdaterRegistry;

    /**
     * Necessary for frontend operations product data keys
     *
     * @var array
     */
    protected array $productKeys = [
        'absolute_price',
        'type_id'
    ];

    /**
     * Option attributes which should be loaded after the main query
     *
     * @var array
     */
    protected array $optionAfterLoadAttributes = [
        'description'
    ];

    public function __construct(
        RequestInterface                       $request,
        \Magento\Framework\Pricing\Helper\Data $pricingHelper,
        Data                                   $catalogData,
        ManagerInterface                       $eventManager,
        DataObjectFactory                      $dataObjectFactory,
        Format                                 $localeFormat,
        PriceCurrencyInterface                 $priceCurrency,
        OptionAttributes                       $optionAttributes,
        OptionValueAttributes                  $optionValueAttributes,
        BaseHelper                             $baseHelper,
        BasePriceHelper                        $basePriceHelper,
        CollectionUpdaterRegistry              $collectionUpdaterRegistry,
        TaxData                                $taxData
    ) {
        $this->request                   = $request;
        $this->eventManager              = $eventManager;
        $this->pricingHelper             = $pricingHelper;
        $this->catalogData               = $catalogData;
        $this->dataObjectFactory         = $dataObjectFactory;
        $this->localeFormat              = $localeFormat;
        $this->priceCurrency             = $priceCurrency;
        $this->optionAttributes          = $optionAttributes;
        $this->optionValueAttributes     = $optionValueAttributes;
        $this->baseHelper                = $baseHelper;
        $this->basePriceHelper           = $basePriceHelper;
        $this->taxData                   = $taxData;
        $this->collectionUpdaterRegistry = $collectionUpdaterRegistry;
    }

    /**
     * Get json representation of
     *
     * @param Product $product
     * @return string
     */
    public function getJsonConfig($product)
    {
        $config = [];
        foreach ($product->getOptions() as $option) {
            /* @var $option Option */
            if ($option->hasValues()) {
                $tmpPriceValues = [];
                foreach ($option->getValues() as $valueId => $value) {
                    $tmpPriceValues[$valueId] = $this->getPriceConfiguration($value);
                }
                $priceValue = $tmpPriceValues;
            } else {
                $priceValue = $this->getPriceConfiguration($option);
            }
            $config[$option->getId()] = $priceValue;
        }

        $configObj = $this->dataObjectFactory->create();
        $configObj->setData('config', $config);

        //pass the return array encapsulated in an object for the other modules to be able to alter it eg: weee
        $this->eventManager->dispatch('catalog_product_option_price_configuration_after', ['configObj' => $configObj]);

        $config = $configObj->getConfig();

        return $this->baseHelper->jsonEncode($config);
    }

    /**
     * Get price configuration
     *
     * @param Value|Option $option
     * @return array
     */
    protected function getPriceConfiguration($option)
    {
        $optionPrice = $option->getPrice(true);
        if ($option->getPriceType() !== Value::TYPE_PERCENT) {
            $optionPrice = $this->pricingHelper->currency($optionPrice, false, false);
        }

        return [
            'prices' => [
                'oldPrice'   => [
                    'amount'      => $this->pricingHelper->currency($option->getRegularPrice(), false, false),
                    'adjustments' => [],
                ],
                'basePrice'  => [
                    'amount' => $this->catalogData->getTaxPrice(
                        $option->getProduct(),
                        $optionPrice,
                        false,
                        null,
                        null,
                        null,
                        null,
                        null,
                        false
                    ),
                ],
                'finalPrice' => [
                    'amount' => $this->catalogData->getTaxPrice(
                        $option->getProduct(),
                        $optionPrice,
                        true,
                        null,
                        null,
                        null,
                        null,
                        null,
                        false
                    ),
                ],
            ],
            'type'   => $option->getPriceType(),
            'name'   => $option->getTitle(),
        ];
    }

    /**
     * Get system data
     *
     * @param string $area
     * @return string (JSON)
     */
    public function getSystemJsonConfig($area)
    {
        $router = $action = '';
        if ($this->request->getRouteName() == 'checkout') {
            $router = 'checkout';
        }
        if ($this->request->getRouteName() == 'sales' && $this->request->getControllerName() == 'order_create'
            || $this->request->getFullActionName() == 'mageworx_optionbase_config_get'
        ) {
            $router = 'admin_order_create';
            $action = $this->request->getActionName();
        }

        $data = [
            'area'   => $area == '' ? 'frontend' : $area,
            'router' => $router,
            'action' => $action
        ];

        return $this->baseHelper->jsonEncode($data);
    }

    /**
     * Get necessary for frontend product data
     *
     * @param Product $product
     * @return string (JSON)
     */
    public function getProductJsonConfig($product)
    {
        $productData   = $product->getData();
        $processedData = [];

        foreach ($this->productKeys as $key) {
            if (isset($productData[$key])) {
                $processedData[$key] = $productData[$key];
            }
        }

        $processedData['extended_tier_prices']   = $this->getExtendedTierPricesConfig($product);
        $processedData['regular_price_excl_tax'] = $this->priceCurrency->convert(
            $this->getProductRegularPrice($product, false)
        );
        $processedData['regular_price_incl_tax'] = $this->priceCurrency->convert(
            $this->getProductRegularPrice($product, true)
        );
        $processedData['final_price_excl_tax']   = $this->priceCurrency->convert(
            $this->getProductFinalPrice($product, false)
        );
        $processedData['final_price_incl_tax']   = $this->priceCurrency->convert(
            $this->getProductFinalPrice($product, true)
        );

        $processedData['is_display_both_prices'] = $this->basePriceHelper->isPriceDisplayModeBothTax();

        if (!empty($productData['price'])) {
            $processedData['price'] = $this->priceCurrency->convert($productData['price']);
        }

        return $this->baseHelper->jsonEncode($processedData);
    }

    /**
     * Get product's tier price config for frontend calculations
     *
     * @param Product $product
     * @return array
     */
    protected function getExtendedTierPricesConfig($product)
    {
        $tierPrices     = [];
        $tierPricesList = $product->getPriceInfo()->getPrice('tier_price')->getTierPriceList();
        foreach ($tierPricesList as $tierPriceItem) {
            $tierPrices[] = [
                'price_excl_tax' => $this->priceCurrency->convert(
                    $this->getProductFinalPrice($product, false, $tierPriceItem['price_qty'])
                ),
                'price_incl_tax' => $this->priceCurrency->convert(
                    $this->getProductFinalPrice($product, true, $tierPriceItem['price_qty'])
                ),
                'qty'            => $tierPriceItem['price_qty']
            ];
        }

        return $tierPrices;
    }

    /**
     * Get locale price format
     *
     * @return string (JSON)
     */
    public function getLocalePriceFormat()
    {
        $data                = $this->localeFormat->getPriceFormat();
        $data['priceSymbol'] = $this->priceCurrency->getCurrency()->getCurrencySymbol();

        return $this->baseHelper->jsonEncode($data);
    }

    /**
     * @param Product $product
     * @param bool|null $includeTax
     * @param int $qty
     * @return float
     */
    public function getProductFinalPrice($product, $includeTax = null, $qty = 1)
    {
        $finalPrice = $product
            ->getPriceModel()
            ->getBasePrice($product, $qty);

        return $this->basePriceHelper->getTaxPrice($product, min($finalPrice, $product->getFinalPrice()), $includeTax);
    }

    /**
     * @param Product $product
     * @param null $includeTax
     * @return float
     */
    public function getProductRegularPrice($product, $includeTax = null)
    {
        return $this->basePriceHelper->getTaxPrice($product, $product->getPrice(), $includeTax);
    }

    /**
     * Get type of price display from the tax config
     * Returns 1 - without tax, 2 - with tax, 3 - both
     *
     * @return int
     */
    public function getPriceDisplayMode()
    {
        return $this->basePriceHelper->getPriceDisplayMode();
    }

    /**
     * Get flag: is catalog price already contains tax
     *
     * @return bool
     */
    public function getCatalogPriceContainsTax()
    {
        return $this->basePriceHelper->getCatalogPriceContainsTax();
    }

    /**
     * Get Product ID
     *
     * @param Product $product
     * @return int
     */
    public function getProductId($product)
    {
        return $product->getData($this->baseHelper->getLinkField());
    }

    /**
     * Store options data in another config,
     * because if we add options data to the main config it generates fatal errors
     *
     * @param Product $product
     * @return string {JSON}
     */
    public function getExtendedOptionsConfig($product)
    {
        $config = [];
        /** @var \MageWorx\OptionBase\Model\Product\Option\AbstractAttribute[] $optionAttributes */
        $optionAttributes      = $this->optionAttributes->getData();
        $optionValueAttributes = $this->optionValueAttributes->getData();
        /** @var Option $option */
        if (empty($product->getOptions())) {
            return $this->baseHelper->jsonEncode($config);
        }

        $valueAttributesToExclude = $this->optionValueAttributes->getAttributesToDisplayOnFrontend();
        $optionValueAttributeData = $this->getValueAttributesDataForFrontend(
            $valueAttributesToExclude,
            $optionValueAttributes
        );

        // Load attributes that are excluded from the main query and are necessary for the frontend
        $this->loadOptionsData($product, $optionAttributes);

        foreach ($product->getOptions() as $optionKey => $option) {
            foreach ($optionAttributes as $optionAttribute) {
                $preparedData = $optionAttribute->prepareDataForFrontend($option);
                if (empty($preparedData) || !is_array($preparedData)) {
                    continue;
                }
                foreach ($preparedData as $preparedDataKey => $preparedDataValue) {
                    $config[$option->getId()][$preparedDataKey] = $preparedDataValue;
                }
            }
            /** @var Value $value */
            if (empty($option->getValues())) {
                $config[$option->getId()]['price_type'] = $option->getPriceType();
                $config[$option->getId()]['price']      = $option->getPrice(false);
                continue;
            }
            foreach ($option->getValues() as $value) {
                foreach ($optionValueAttributes as $optionValueAttribute) {
                    $valueAttributeName = $optionValueAttribute->getName();
                    $valueAttributeData = $optionValueAttributeData[$valueAttributeName] ?? null;
                    if ($valueAttributeData && isset($valueAttributeData[$value->getId()])) {
                        $value->setData($valueAttributeName, $valueAttributeData[$value->getId()]);
                    }
                    $preparedData = $optionValueAttribute->prepareDataForFrontend($value);

                    if (empty($preparedData) || !is_array($preparedData)) {
                        continue;
                    }
                    foreach ($preparedData as $preparedDataKey => $preparedDataValue) {
                        $config[$option->getId()]['values'][$value->getId()][$preparedDataKey] = $preparedDataValue;
                    }
                }

                $config[$option->getId()]['values'][$value->getId()]['title']      = $value->getTitle();
                $config[$option->getId()]['values'][$value->getId()]['price_type'] = $value->getPriceType();
                $config[$option->getId()]['values'][$value->getId()]['price']      = $value->getPrice(false);
            }
        }

        return $this->baseHelper->jsonEncode($config);
    }

    /**
     * @param Product $product
     * @param \MageWorx\OptionBase\Model\Product\Option\AbstractAttribute[] $optionAttributes
     * @return void
     */
    private function loadOptionsData(Product $product, array $optionAttributes): void
    {
        // Collect option ids
        $optionIds = $this->collectionUpdaterRegistry->getOptionIds();

        foreach ($optionAttributes as $optionAttribute) {
            if (in_array($optionAttribute->getName(), $this->optionAfterLoadAttributes)) {
                $attributeData = $optionAttribute->loadAttributeData($product, $optionIds);

                foreach ($product->getOptions() as $option) {
                    $option->setData($optionAttribute->getName(), $attributeData[$option->getId()][$optionAttribute->getName()] ?? null);
                }
            }
        }
    }

    /**
     * Get Value attributes data for frontend
     *
     * @param array $valueAttributesToExclude
     * @param array $optionValueAttributes
     * @return array
     */
    protected function getValueAttributesDataForFrontend(array $valueAttributesToExclude, array $optionValueAttributes): array
    {
        $optionTypeIds = [];
        if (!empty($this->collectionUpdaterRegistry->getOptionValueIds())) {
            $optionTypeIds = $this->collectionUpdaterRegistry->getOptionValueIds();
        }

        $optionValueAttributeData = [];
        foreach ($optionValueAttributes as $optionValueAttribute) {
            $valueAttributeName = $optionValueAttribute->getName();

            if ($this->validateValueAttributeForUsingOnFrontend($valueAttributeName, $valueAttributesToExclude)) {
                $optionValueAttributeData[$valueAttributeName] = $optionValueAttribute->getValueAttributesData(
                    $optionTypeIds
                );
            }
        }

        return $optionValueAttributeData;
    }

    /**
     * Validate value attributes for frontend
     *
     * @param string $valueAttributeName
     * @param array $valueAttributesToExclude
     * @return bool
     */
    protected function validateValueAttributeForUsingOnFrontend(string $valueAttributeName, array $valueAttributesToExclude): bool
    {
        return in_array($valueAttributeName, $valueAttributesToExclude);
    }
}
