<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionBase\Plugin;

use Magento\Catalog\Block\Product\View\Options;
use Magento\Catalog\Helper\Data;
use Magento\Catalog\Model\Product\Option;
use Magento\Catalog\Model\Product\Option\Value;
use Magento\Catalog\Model\Product\Type\Price;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\ArrayUtils;
use Magento\Framework\View\Element\Template\Context;
use MageWorx\OptionBase\Helper\Price as BasePriceHelper;
use Magento\Framework\Json\DecoderInterface;

class ExtendPriceConfig extends Options
{
    protected PriceCurrencyInterface $priceCurrency;
    protected BasePriceHelper $basePriceHelper;
    protected DecoderInterface $jsonDecoder;

    public function __construct(
        Context $context,
        \Magento\Framework\Pricing\Helper\Data $pricingHelper,
        Data $catalogData,
        EncoderInterface $jsonEncoder,
        Option $option,
        Registry $registry,
        ArrayUtils $arrayUtils,
        PriceCurrencyInterface $priceCurrency,
        BasePriceHelper $basePriceHelper,
        DecoderInterface $jsonDecoder,
        array $data = []
    ) {
        $this->priceCurrency   = $priceCurrency;
        $this->basePriceHelper = $basePriceHelper;
        $this->jsonDecoder     = $jsonDecoder;
        parent::__construct($context, $pricingHelper, $catalogData, $jsonEncoder, $option, $registry, $arrayUtils);
    }

    /**
     * Extend price config with suitable special price on frontend
     *
     * @param Options $subject
     * @param callable $proceed
     * @return mixed
     */
    public function aroundGetJsonConfig(Options $subject, $proceed)
    {
        if (!$subject->hasOptions()) {
            return $proceed();
        }

        $defaultConfig  = $this->jsonDecoder->decode($proceed());
        $extendedConfig = $defaultConfig;

        foreach ($subject->getOptions() as $option) {
            /* @var $option Option */
            $values = $option->getValues();

            if(!isset($defaultConfig[$option->getId()])) {
                continue;
            }

            if (!empty($values) && $option->hasValues()) {
                foreach ($values as $valueId => $value) {
                    $config = $this->getExtendedOptionValueJsonConfig($defaultConfig, $option, $valueId, $value);
                    if(empty($config)) {
                        continue;
                    }

                    $config['title'] = $value->getTitle();
                    $extendedConfig[$option->getId()][$valueId] = array_merge(
                        $defaultConfig[$option->getId()][$valueId],
                        $config
                    );
                }
            } else {
                $config = $this->getExtendedOptionJsonConfig($defaultConfig, $option);
                if(empty($config)) {
                    continue;
                }

                $config['title'] = $option->getTitle();
                $extendedConfig[$option->getId()] = array_merge(
                    $defaultConfig[$option->getId()],
                    $config
                );
            }
        }

        return $this->_jsonEncoder->encode($extendedConfig);
    }

    public function getExtendedOptionJsonConfig(array $defaultConfig, Option $option): array
    {
        $config = [];

        if(!isset($defaultConfig[$option->getId()]['prices']['oldPrice']['amount'])) {
            return $config;
        }

        $defaultConfigOptionId = $defaultConfig[$option->getId()];

        $config['prices']['oldPrice']['amount']          = $defaultConfigOptionId['prices']['oldPrice']['amount'];
        $config['prices']['oldPrice']['amount_excl_tax'] = $config['prices']['oldPrice']['amount'];
        $config['prices']['oldPrice']['amount_incl_tax'] = $this->basePriceHelper->getTaxPrice(
            $this->getProduct(),
            $config['prices']['oldPrice']['amount'],
            true
        );

        $config['prices']['basePrice']['amount']  = $defaultConfigOptionId['prices']['basePrice']['amount'];
        $config['prices']['finalPrice']['amount'] = $defaultConfigOptionId['prices']['finalPrice']['amount'];
        $config['valuePrice']                     = $this->priceCurrency->format(
            $config['prices']['oldPrice']['amount'],
            false
        );

        return $config;
    }

    public function getExtendedOptionValueJsonConfig(
        array $defaultConfig,
        Option $option,
        int $valueId,
        Value $value
    ): array {
        $config = [];

        if(!isset($defaultConfig[$option->getId()][$valueId]['prices']['oldPrice']['amount'])) {
            return $config;
        }

        $defaultConfigOptionId = $defaultConfig[$option->getId()];

        $config['prices']['oldPrice']['amount']          = $defaultConfigOptionId[$valueId]['prices']['oldPrice']['amount'];
        $config['prices']['oldPrice']['amount_excl_tax'] = $config['prices']['oldPrice']['amount'];
        $config['prices']['oldPrice']['amount_incl_tax'] = $this->basePriceHelper->getTaxPrice(
            $this->getProduct(),
            $config['prices']['oldPrice']['amount'],
            true
        );

        $config['prices']['basePrice']['amount']  = $defaultConfigOptionId[$valueId]['prices']['basePrice']['amount'];
        $config['prices']['finalPrice']['amount'] = $defaultConfigOptionId[$valueId]['prices']['finalPrice']['amount'];
        $config['valuePrice']                     = $this->priceCurrency->format(
            $config['prices']['oldPrice']['amount'],
            false
        );

        return $config;
    }
}
