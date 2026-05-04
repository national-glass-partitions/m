<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionFeatures\Model\Product\Option;

use Magento\Catalog\Model\Product\Option;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionFeatures\Helper\Data as Helper;

class AdditionalHtml
{
    protected Helper $helper;
    protected BaseHelper $baseHelper;
    protected Option $option;
    protected \DOMDocument $dom;

    public function __construct(
        Helper $helper,
        BaseHelper $baseHelper
    ) {
        $this->helper     = $helper;
        $this->baseHelper = $baseHelper;
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

        $this->dom    = $dom;
        $this->option = $option;

        $this->setDivClass();
        $this->setSelectionLimit();
        $this->hideIsHidden();

        return;
    }

    /**
     * Set div class for option
     *
     * @return void
     */
    protected function setDivClass()
    {
        $xpath          = new \DOMXPath($this->dom);
        $optionCssClass = $xpath->query('//div')->item(0)->getAttribute('class') ?: '';
        $xpath->query('//div')
              ->item(0)
              ->setAttribute('class', $optionCssClass . ' ' . $this->option->getDivClass());
    }

    /**
     * @return void
     */
    protected function setSelectionLimit()
    {
        if ($this->baseHelper->isCheckbox($this->option)) {
            $this->addHtmlToCheckbox();
        } elseif ($this->baseHelper->isMultiselect($this->option)) {
            $this->addHtmlToMultiselect();
        }
        $this->addSelectionLimitMessage();
    }

    /**
     * Hide checkbox if IsHidden = true
     *
     * @return void
     */
    protected function hideIsHidden()
    {
        if ($this->baseHelper->isCheckbox($this->option) && $this->option->getData(Helper::KEY_IS_HIDDEN)) {
            $this->hideCheckbox();
        }
    }

    /**
     * Add selection limit html for checkbox
     *
     * @return void
     */
    protected function addHtmlToCheckbox()
    {
        $xpath = new \DOMXPath($this->dom);

        $optionDivs = $xpath->query("//*[@name='options[" . $this->option->getOptionId() . "][]']");
        foreach ($optionDivs as $optionDiv) {
            $optionCssClass = $optionDiv->getAttribute('class') ?: '';
            $optionDiv->setAttribute('class', $optionCssClass . ' ' . 'mageworx-selection-limit');
        }
    }

    /**
     * Hide checkbox
     *
     * @return void
     */
    protected function hideCheckbox()
    {
        $xpath = new \DOMXPath($this->dom);

        $optionCssClass = $xpath->query('//div')->item(0)->getAttribute('class') ?: '';
        $xpath->query('//div')
              ->item(0)
              ->setAttribute('class', $optionCssClass  . ' ' . 'mageworx-hidden');
    }

    /**
     * Add selection limit html for multiselect, multiswatch
     *
     * @return void
     */
    protected function addHtmlToMultiselect()
    {
        $xpath = new \DOMXPath($this->dom);

        $optionDiv = $xpath->query("//*[@name='options[" . $this->option->getOptionId() . "][]']")
                           ->item(0);

        $optionCssClass = $optionDiv->getAttribute('class') ?: '';
        $xpath->query("//*[@name='options[" . $this->option->getOptionId() . "][]']")
              ->item(0)
              ->setAttribute('class', $optionCssClass . ' ' . 'mageworx-selection-limit');
    }

    /**
     * Add selection limit message under option
     *
     * @return void
     */
    protected function addSelectionLimitMessage()
    {
        if (!$this->isMultiSelection($this->option)) {
            return;
        }

        if (!$this->option->getSelectionLimitFrom() && !$this->option->getSelectionLimitTo()) {
            return;
        }
        $html = '<ul class="items"><li class="item"><i>';

        $selectionLimitMessage = $this->helper->getSelectionLimitMessage(
            $this->option->getSelectionLimitFrom(),
            $this->option->getSelectionLimitTo()
        );

        $html .= $selectionLimitMessage;
        $html .= '</i></li></ul>';

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
        $targetNode->parentNode->insertBefore($this->dom->createTextNode("\n"), $targetNode->nextSibling);
    }

    /**
     * @param \DOMDocument $dom
     * @param Option $option
     * @return bool
     */
    protected function out($dom, $option)
    {
        if (!$dom || !$option) {
            return true;
        }
        return false;
    }

    /**
     * Check if option is multiselection
     *
     * @param $option
     * @return bool
     */
    protected function isMultiSelection($option)
    {
        $multiple = [
            \Magento\Catalog\Model\Product\Option::OPTION_TYPE_MULTIPLE,
            \Magento\Catalog\Model\Product\Option::OPTION_TYPE_CHECKBOX,
        ];

        return in_array($option->getType(), $multiple);
    }
}
