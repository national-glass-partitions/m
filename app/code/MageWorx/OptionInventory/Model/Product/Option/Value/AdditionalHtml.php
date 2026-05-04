<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionInventory\Model\Product\Option\Value;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Option;
use MageWorx\OptionInventory\Helper\Data as Helper;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionInventory\Helper\Stock as StockHelper;

class AdditionalHtml
{
    protected Helper $helper;
    protected StockHelper $stockHelper;
    protected BaseHelper $baseHelper;
    protected ProductRepositoryInterface $productRepository;

    /**
     * @param Helper $helper
     * @param BaseHelper $baseHelper
     * @param StockHelper $stockHelper
     */
    public function __construct(
        Helper $helper,
        StockHelper $stockHelper,
        BaseHelper $baseHelper,
        ProductRepositoryInterface $productRepository
    ) {
        $this->helper      = $helper;
        $this->baseHelper  = $baseHelper;
        $this->stockHelper = $stockHelper;
        $this->productRepository = $productRepository;
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

        $isDisabledOutOfStockOptions = $this->baseHelper->isDisabledOutOfStockOptions();

        $xpath = new \DOMXPath($dom);
        $count = 1;
        foreach ($option->getValues() as $value) {
            $count++;
            if (empty($value['manage_stock'])) {
                continue;
            }
            if ($this->baseHelper->isCheckbox($option) || $this->baseHelper->isRadio($option)) {
                $element       = $xpath
                    ->query('//div/div[descendant::label[@for="options_' . $option->getId() . '_' . $count . '"]]')
                    ->item(0);
                $elementSelect = $element
                    ->getElementsByTagName('input')
                    ->item(0);
                $elementTitle  = $xpath
                    ->query('//label[@for="options_' . $option->getId() . '_' . $count . '"]')
                    ->item(0);
            } elseif ($this->baseHelper->isDropdown($option) || $this->baseHelper->isMultiselect($option)) {
                $element = $elementSelect = $elementTitle = $xpath
                    ->query('//option[@value="' . $value->getId() . '"]')
                    ->item(0);
            }

            if ($this->baseHelper->isModuleEnabled('Magento_InventorySalesAdminUi') && $this->validateSku($value)) {
                $originSku = $this->productRepository->get($value->getSku())->getSku();
                $value->setQty($this->baseHelper->updateValueQtyToSalableQty($originSku));
            }

            $isOutOfStockOption = $this->stockHelper->isOutOfStockOption($value);
            if ($isOutOfStockOption) {
                if (!$isDisabledOutOfStockOptions) {
                    $this->stockHelper->hideOutOfStockOption($element);
                    continue;
                } else {
                    $this->stockHelper->disableOutOfStockOption($elementSelect);
                }
            }

            $stockMessage = $this->stockHelper->getStockMessage($value, $option->getProductId());
            if ($stockMessage) {
                $this->stockHelper->setStockMessage($dom, $elementTitle, $stockMessage);
            }
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
        if (!$this->helper->isEnabledOptionInventory()) {
            return true;
        }

        return (!$dom || !$option);
    }

    /**
     * * Check if sku is valid
     *
     * @param \Magento\Catalog\Api\Data\ProductCustomOptionValuesInterface $value
     * @return bool
     */
    protected function validateSku(\Magento\Catalog\Api\Data\ProductCustomOptionValuesInterface $value): bool
    {
        if (isset($value['sku_is_valid']) && $value['sku_is_valid']) {
            return true;
        }

        return false;
    }
}
