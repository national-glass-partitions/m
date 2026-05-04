<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionAdvancedPricing\Model\Product\Option;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Option;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionBase\Helper\Price as BasePriceHelper;
use MageWorx\OptionBase\Model\HiddenDependents as HiddenDependentsModel;
use MageWorx\OptionAdvancedPricing\Helper\Data as Helper;
use MageWorx\OptionAdvancedPricing\Model\TierPrice as TierPriceModel;

class AdditionalHtml
{
    protected Helper $helper;
    protected BaseHelper $baseHelper;
    protected BasePriceHelper $basePriceHelper;
    protected PriceCurrencyInterface $priceCurrency;
    protected Option $option;
    protected Product $product;
    protected TierPriceModel $tierPriceModel;
    protected \DOMDocument $dom;
    protected HiddenDependentsModel $hiddenDependentsModel;

    /**
     * AdditionalHtml constructor.
     * @param Helper $helper
     * @param BaseHelper $baseHelper
     * @param BasePriceHelper $basePriceHelper
     * @param TierPriceModel $tierPriceModel
     * @param PriceCurrencyInterface $priceCurrency
     * @param HiddenDependentsModel $hiddenDependentsModel
     */
    public function __construct(
        Helper $helper,
        BaseHelper $baseHelper,
        BasePriceHelper $basePriceHelper,
        TierPriceModel $tierPriceModel,
        PriceCurrencyInterface $priceCurrency,
        HiddenDependentsModel $hiddenDependentsModel
    ) {
        $this->helper                = $helper;
        $this->baseHelper            = $baseHelper;
        $this->basePriceHelper       = $basePriceHelper;
        $this->priceCurrency         = $priceCurrency;
        $this->tierPriceModel        = $tierPriceModel;
        $this->hiddenDependentsModel = $hiddenDependentsModel;
    }

    /**
     * @param \DOMDocument $dom
     * @param Option $option
     * @return void
     */
    public function getAdditionalHtml($dom, $option)
    {
        if ($this->out($dom, $option)) {
            return;
        }

        $this->dom     = $dom;
        $this->option  = $option;
        $this->product = $option->getProduct();

        if ($option->getPrice() > 0) {
            $this->addPricePerCharacterInPrice();
            $this->addHtmlToFieldOption();
        }

        libxml_clear_errors();

        return;
    }

    /**
     * @param \DOMDocument $dom
     * @param Option $option
     * @return bool
     */
    protected function out($dom, $option)
    {
        if ($option->getPriceType() !== Helper::PRICE_TYPE_PER_CHARACTER) {
            return true;
        }

        return false;
    }

    /**
     * Add price per character
     */
    protected function addHtmlToFieldOption()
    {
        $html = '<p class="note">' . __('Total price for characters') .
            ': ' . $this->baseHelper->getConvertEncoding($this->priceCurrency->getCurrencySymbol()) .
            '<span id="price_per_character_' . $this->option->getId() . '">' . 0 . '</span></p>';

        $xpath      = new \DOMXPath($this->dom);
        $targetNode = $xpath->query("//div[contains(@class,'control')]");
        if (!$targetNode->length) {
            return;
        }

        $tpl = new \DOMDocument();
        $tpl->loadHtml($html);

        $targetNode = $targetNode->item(0);
        $newNode    = $this->dom->importNode($tpl->documentElement, true);
        $targetNode->parentNode->insertBefore($newNode, $targetNode->nextSibling);
    }

    /**
     * Add Price per character note to option price
     */
    private function addPricePerCharacterInPrice()
    {
        $html       = ' <span class="price_per_character">' . __('per character') . '</span>';
        $xpath      = new \DOMXPath($this->dom);
        $targetNode = $xpath->query("//label[contains(@class,'label')]");

        if (!$targetNode->length) {
            return;
        }

        $tpl = new \DOMDocument();
        $tpl->loadHtml($html);

        $targetNode = $targetNode->item(0);
        $newNode    = $this->dom->importNode($tpl->documentElement, true);
        $targetNode->insertBefore($newNode);
        $targetNode->insertBefore($this->dom->createTextNode("\n"));
    }
}
