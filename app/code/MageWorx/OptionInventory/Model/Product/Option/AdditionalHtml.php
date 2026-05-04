<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionInventory\Model\Product\Option;

use Magento\Catalog\Model\Product\Option;
use MageWorx\OptionInventory\Helper\Data as HelperData;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionInventory\Helper\Stock as StockHelper;

class AdditionalHtml
{
    protected HelperData $helperData;
    protected StockHelper $stockHelper;
    protected BaseHelper $baseHelper;

    /**
     * AdditionalHtml constructor.
     *
     * @param HelperData $helperData
     * @param StockHelper $stockHelper
     * @param BaseHelper $baseHelper
     */
    public function __construct(
        HelperData $helperData,
        StockHelper $stockHelper,
        BaseHelper $baseHelper
    ) {
        $this->helperData  = $helperData;
        $this->baseHelper  = $baseHelper;
        $this->stockHelper = $stockHelper;
    }

    /**
     * Work only for setting 'out of stock option' - hide
     *
     * @param \DOMDocument $dom
     * @param Option $option
     */
    public function getAdditionalHtml(\DOMDocument $dom, \Magento\Catalog\Model\Product\Option $option): void
    {
        if ($this->out($dom, $option)) {
            return;
        }

        $isAllValuesOutOfStock = $this->isAllValuesOutOfStock($option);


        if (!$isAllValuesOutOfStock) {
            return;
        }

        if ($this->helperData->isDisplayOutOfStockMessageOnOptionsLevel()) {
            $this->processAddOutOfStockMessage($dom);
        }

        if (!$this->helperData->isDisplayOutOfStockOptions() &&
            !$this->helperData->isRequireHiddenOutOfStockOptions()) {
            $this->processHideOption($dom, $option);
        }
    }

    public function processAddOutOfStockMessage(\DOMDocument $dom): void
    {
        $outOfStockMessage = '(' . __('Out Of Stock') . ')';
        $xpath             = new \DOMXPath($dom);
        $optionLabel       = $xpath->query(
            "//label[contains(@class,'label')]"
        );
        $optionTitle       = $optionLabel->item(
            0
        )->textContent;
        $xpath->query("//label[contains(@class,'label')]")->item(
            0
        )->textContent     = $optionTitle . '- ' . $outOfStockMessage;
        libxml_clear_errors();

        return;
    }

    public function isAllValuesOutOfStock(\Magento\Catalog\Model\Product\Option $option): bool
    {
        foreach ($option->getValues() as $value) {
            if (!$this->stockHelper->isOutOfStockOption($value)) {

                return false;
            }
        }

        return true;
    }

    public function processHideOption(\DOMDocument $dom, \Magento\Catalog\Model\Product\Option $option): void
    {
        $xpath          = new \DOMXPath($dom);
        $optionCssStyle = $xpath->query('//div')->item(0)->getAttribute('style') ?: '';
        $xpath->query('//div')
              ->item(0)
              ->setAttribute('style', 'display: none;' . $optionCssStyle);

        if ($option->getData('is_swatch') && $option->getType() == 'drop_down') {
            $optionClass  = $xpath->query('//select')->item(0)->getAttribute('class');
            $updatedClass = str_replace("mageworx-swatch", "", $optionClass);
            $xpath->query('//select')->item(0)->setAttribute('class', $updatedClass);
        }

        libxml_clear_errors();

        return;
    }

    protected function out(\DOMDocument $dom, \Magento\Catalog\Model\Product\Option $option): bool
    {
        if (!$this->helperData->isEnabledOptionInventory()) {
            return true;
        }

        if (empty($option->getProduct())) {
            return true;
        }

        if (!$this->baseHelper->isSelectableOption($option->getType())) {
            return true;
        }

        return (!$dom || !$option);
    }
}
