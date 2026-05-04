<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionBase\Block\Product\View;

use Magento\Catalog\Helper\Data;
use Magento\Catalog\Model\Product\Option;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Locale\Format;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\ArrayUtils;
use Magento\Framework\View\Element\Template\Context;
use \MageWorx\OptionBase\Model\Product\Option\Attributes as OptionAttributes;
use \MageWorx\OptionBase\Model\Product\Option\Value\Attributes as OptionValueAttributes;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionBase\Helper\Price as BasePriceHelper;
use MageWorx\OptionBase\Model\Config\Base as BaseConfig;

class Options extends \Magento\Catalog\Block\Product\View\Options
{
    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    protected $pricingHelper;
    protected Format $localeFormat;
    protected PriceCurrencyInterface $priceCurrency;
    protected OptionAttributes $optionAttributes;
    protected OptionValueAttributes $optionValueAttributes;
    protected BaseHelper $baseHelper;
    protected BasePriceHelper $basePriceHelper;
    protected BaseConfig $baseConfig;

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
     * Options constructor.
     *
     * @param Context $context
     * @param \Magento\Framework\Pricing\Helper\Data $pricingHelper
     * @param Data $catalogData
     * @param EncoderInterface $jsonEncoder
     * @param Option $option
     * @param Registry $registry
     * @param ArrayUtils $arrayUtils
     * @param Format $localeFormat
     * @param PriceCurrencyInterface $priceCurrency
     * @param OptionAttributes $optionAttributes
     * @param OptionValueAttributes $optionValueAttributes
     * @param BaseHelper $baseHelper
     * @param BasePriceHelper $basePriceHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Pricing\Helper\Data $pricingHelper,
        Data $catalogData,
        EncoderInterface $jsonEncoder,
        Option $option,
        Registry $registry,
        ArrayUtils $arrayUtils,
        Format $localeFormat,
        PriceCurrencyInterface $priceCurrency,
        OptionAttributes $optionAttributes,
        OptionValueAttributes $optionValueAttributes,
        BaseHelper $baseHelper,
        BasePriceHelper $basePriceHelper,
        BaseConfig $baseConfig,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $pricingHelper,
            $catalogData,
            $jsonEncoder,
            $option,
            $registry,
            $arrayUtils,
            $data
        );
        $this->localeFormat          = $localeFormat;
        $this->priceCurrency         = $priceCurrency;
        $this->optionAttributes      = $optionAttributes;
        $this->optionValueAttributes = $optionValueAttributes;
        $this->baseHelper            = $baseHelper;
        $this->basePriceHelper       = $basePriceHelper;
        $this->baseConfig            = $baseConfig;
    }

    /**
     * Get system data
     *
     * @param string $area
     * @return string (JSON)
     */
    public function getSystemJsonConfig($area)
    {
        return $this->baseConfig->getSystemJsonConfig($area);
    }

    /**
     * Get necessary for frontend product data
     *
     * @return string (JSON)
     */
    public function getProductJsonConfig()
    {
        $product = $this->getProduct();
        return $this->baseConfig->getProductJsonConfig($product);
    }

    /**
     * @return string (JSON)
     */
    public function getLocalePriceFormat()
    {
        $data                = $this->localeFormat->getPriceFormat();
        $data['priceSymbol'] = $this->priceCurrency->getCurrency()->getCurrencySymbol();

        return $this->_jsonEncoder->encode($data);
    }

    /**
     * @param bool|null $includeTax
     * @param int $qty
     * @return float
     */
    public function getProductFinalPrice($includeTax = null, $qty = 1)
    {
        $product = $this->getProduct();
        return $this->baseConfig->getProductFinalPrice($product, $includeTax, $qty);
    }

    /**
     * @param null $includeTax
     * @return float
     */
    public function getProductRegularPrice($includeTax = null)
    {
        $product = $this->getProduct();
        return $this->baseConfig->getProductRegularPrice($product, $includeTax);
    }

    /**
     * Get type of price display from the tax config
     * Returns 1 - without tax, 2 - with tax, 3 - both
     *
     * @return integer
     */
    public function getPriceDisplayMode()
    {
        return $this->baseConfig->getPriceDisplayMode();
    }

    /**
     * Get flag: is catalog price already contains tax
     *
     * @return int
     */
    public function getCatalogPriceContainsTax()
    {
        return $this->baseConfig->getCatalogPriceContainsTax();
    }

    /**
     * Store options data in another config,
     * because if we add options data to the main config it generates fatal errors
     *
     * @return string {JSON}
     */
    public function getExtendedOptionsConfig()
    {
        $product = $this->getProduct();
        return $this->baseConfig->getExtendedOptionsConfig($product);
    }

    /**
     * Get update URL
     *
     * @return string
     */
    public function getUpdateUrl()
    {
        return $this->_urlBuilder->getUrl('mageworx_optionbase/config/get');
    }

    /**
     * Get product ID
     *
     * @return int
     */
    public function getProductId()
    {
        $product = $this->getProduct();
        return $this->baseConfig->getProductId($product);
    }
}
