<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionAdvancedPricing\Model\Product\Option\Value;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Option;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionBase\Helper\Price as BasePriceHelper;
use MageWorx\OptionBase\Model\HiddenDependents as HiddenDependentsModel;
use MageWorx\OptionAdvancedPricing\Helper\Data as Helper;
use MageWorx\OptionAdvancedPricing\Model\TierPrice as TierPriceModel;
use Magento\Catalog\Api\Data\ProductCustomOptionValuesInterface;

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
     *
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

        if ($this->baseHelper->isCheckbox($this->option) || $this->baseHelper->isRadio($this->option)) {
            $this->addHtmlToMultiSelectionOption();
        } elseif ($this->baseHelper->isDropdown($this->option) || $this->baseHelper->isMultiselect($this->option)) {
            $this->addHtmlToSingleSelectionOption();
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
        if (!$this->helper->isTierPriceEnabled()
            || !$this->helper->isDisplayTierPriceTableNeeded()
            || !$dom
            || !$option
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param \DOMElement $node
     * @return string
     */
    protected function getInnerHtml(\DOMElement $node)
    {
        $innerHTML = '';
        $children  = $node->childNodes;
        foreach ($children as $child) {
            $innerHTML .= $child->ownerDocument->saveXML($child);
        }

        return $innerHTML;
    }

    /**
     * Get tier price table html
     *
     * @param ProductCustomOptionValuesInterface|Option\Value $value
     * @return string
     */
    protected function getTierPriceHtml($value)
    {
        $tierPrices = $this->tierPriceModel->getSuitableTierPrices($value, true);
        if (!$tierPrices) {
            return '';
        }

        $index         = 1;
        $hiddenValues  = $this->hiddenDependentsModel->getHiddenValues($value->getOption()->getProduct());
        $hiddenOptions = $this->hiddenDependentsModel->getHiddenOptions($value->getOption()->getProduct());

        $display = 'style="display: none"';
        if ($value->getIsDefault()
            && !in_array($value->getOptionTypeId(), $hiddenValues)
            && !in_array($value->getOption()->getOptionId(), $hiddenOptions)
        ) {
            $display = 'style="display: block"';
        }
        $html = '<ul id="value_' . $value->getOptionTypeId()
            . '_tier_price" class="prices-tier items" ' . $display . '>';

        $isPriceDisplayModeBothTax    = $this->basePriceHelper->isPriceDisplayModeBothTax();
        $isPriceDisplayModeIncludeTax = $this->basePriceHelper->isPriceDisplayModeIncludeTax();

        foreach ($tierPrices as $tierPriceItem) {
            $index++;
            $html .= '<li class="item">';
            $for  = '<span class="price-container price-tier_price tax weee">';
            $for  .= '<span class="price-wrapper price-including-tax">';
            $for  .= '<span class="price">';
            if ($isPriceDisplayModeBothTax) {
                $formattedTierPriceInclTax = $this->priceCurrency->format($tierPriceItem['price_incl_tax']);
                $formattedTierPrice        = $this->priceCurrency->format($tierPriceItem['price']);

                $for .= $this->baseHelper->getConvertEncoding($formattedTierPriceInclTax);
                $for .= '</span></span>' . ' ';
                $for .= '<span data-label="' . __('Excl. Tax') . '" class="price-wrapper price-excluding-tax">';
                $for .= '<span class="price">';
                $for .= $this->baseHelper->getConvertEncoding($formattedTierPrice);
            } elseif ($isPriceDisplayModeIncludeTax) {
                $for .= htmlentities($this->priceCurrency->format($tierPriceItem['price_incl_tax'], false));
            } else {
                $for .= htmlentities($this->priceCurrency->format($tierPriceItem['price'], false));
            }
            $for .= '</span></span></span>';

            $qtyAndTitle = $tierPriceItem['qty'];
            if ($this->baseHelper->isMultiselect($value->getOption())) {
                $qtyAndTitle = $tierPriceItem['qty'] . ' (' . $value->getTitle() . ')';
            }

            $html .= $this->baseHelper->getConvertEncoding(__('Buy %1 for %2 each and', $qtyAndTitle, $for));
            $html .= ' ' . '<strong class="benefit">' . __('save');
            $html .= '<span class="percent tier-' . $index . '">' . ' ' . $tierPriceItem['percent'] . '</span>%';
            $html .= '</strong>';
            $html .= '</li>';
        }
        $html .= '</ul>';

        return $html;
    }

    /**
     * Get qty input html for checkbox, radio
     *
     * @return void
     */
    protected function addHtmlToMultiSelectionOption()
    {
        if (empty($this->option->getValues())) {
            return;
        }
        $count = 1;
        foreach ($this->option->getValues() as $value) {
            $count++;
            $html = $this->getTierPriceHtml($value);
            if (!$html) {
                continue;
            }

            $tpl = new \DOMDocument('1.0', 'UTF-8');
            $tpl->loadHtml($html);

            $xpath    = new \DOMXPath($this->dom);
            $idString = 'options_' . $this->option->getId() . '_' . $count;
            $input    = $xpath->query("//*[@id='$idString']")->item(0);

            $input->setAttribute('style', 'vertical-align: middle');
            $input->parentNode->appendChild($this->dom->importNode($tpl->documentElement, true));
        }
    }

    /**
     * Get qty input html for dropdown, swatch
     *
     * @return void
     */
    protected function addHtmlToSingleSelectionOption()
    {
        if (empty($this->option->getValues())) {
            return;
        }
        foreach ($this->option->getValues() as $value) {
            $html = $this->getTierPriceHtml($value);
            if (!$html) {
                continue;
            }
            $body = $this->dom->documentElement->firstChild;
            $tpl  = new \DOMDocument();
            $tpl->loadHtml($html);
            $body->appendChild($this->dom->importNode($tpl->documentElement, true));
        }
    }
}
