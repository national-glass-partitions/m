<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionDependency\Model\Product\Option\Value;

use Magento\Catalog\Model\Product\Option;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionBase\Helper\System as SystemHelper;
use MageWorx\OptionDependency\Model\HiddenDependents as HiddenDependentsModel;
use MageWorx\OptionBase\Model\HiddenDependents as HiddenDependentsStorage;
use Psr\Log\LoggerInterface as Logger;

class AdditionalHtml
{
    protected BaseHelper $baseHelper;
    protected SystemHelper $systemHelper;
    protected Option $option;
    protected \DOMDocument $dom;
    protected Logger $logger;
    protected HiddenDependentsModel $hiddenDependentsModel;
    protected HiddenDependentsStorage $hiddenDependentsStorage;

    /**
     * @param BaseHelper $baseHelper
     * @param SystemHelper $systemHelper
     * @param Logger $logger
     * @param HiddenDependentsModel $hiddenDependentsModel
     * @param HiddenDependentsStorage $hiddenDependentsStorage
     */
    public function __construct(
        BaseHelper $baseHelper,
        Logger $logger,
        SystemHelper $systemHelper,
        HiddenDependentsModel $hiddenDependentsModel,
        HiddenDependentsStorage $hiddenDependentsStorage
    ) {
        $this->baseHelper              = $baseHelper;
        $this->logger                  = $logger;
        $this->systemHelper            = $systemHelper;
        $this->hiddenDependentsModel   = $hiddenDependentsModel;
        $this->hiddenDependentsStorage = $hiddenDependentsStorage;
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

        $this->hideDependents();

        return;
    }

    /**
     *
     * @return void
     */
    protected function hideDependents()
    {
        if (empty($this->option->getProduct())) {
            return;
        }

        try {
            $hiddenDependents = $this->getHiddenDependents();
        } catch (\Exception $exception) {
            $this->logger->critical(
                __("Incorrect option dependency format for product ID") . ": " . $this->option->getProductId()
            );
            return;
        }

        if (empty($hiddenDependents)
            || ((empty($hiddenDependents['hidden_values']) || !is_array($hiddenDependents['hidden_values']))
                && ((empty($hiddenDependents['hidden_options']) || !is_array($hiddenDependents['hidden_options']))))
        ) {
            return;
        }

        $xpath = new \DOMXPath($this->dom);

        $count = 1;
        foreach ($this->option->getValues() as $value) {
            $count++;

            if (!in_array($value->getOptionTypeId(), $hiddenDependents['hidden_values'])
                && !in_array($this->option->getOptionId(), $hiddenDependents['hidden_options'])
            ) {
                continue;
            }

            $select =
                $xpath->query('//option[@value="' . $value->getOptionTypeId() . '"]')->item(0);

            $input =
                $xpath->query(
                    '//div/div[descendant::label[@for="options_' . $this->option->getOptionId() . '_' . $count . '"]]'
                )->item(0);

            $element = $select ? $select : $input;

            if ($element) {
                $optionValueCssStyle = $element->getAttribute('style') ?: '';
                $element->setAttribute("style", 'display: none;' . $optionValueCssStyle);
                if ($this->baseHelper->isDropdown($this->option) || $this->baseHelper->isMultiselect($this->option)) {
                    $element->setAttribute("class", 'mageworx-need-wrap');
                }
            }
        }
    }

    /**
     * Get hidden dependents data considering source
     *
     * @return array
     */
    protected function getHiddenDependents()
    {
        if ($this->systemHelper->isConfigureQuoteItemsAction()
            || $this->systemHelper->isCheckoutCartConfigureAction()
        ) {
            return $this->hiddenDependentsModel->getConfigureQuoteItemsHiddenDependents();
        } else {
            if ($this->systemHelper->isShareableLink()) {
                return $this->hiddenDependentsStorage->getQuoteItemsHiddenDependents();
            }

            if (empty($this->option->getProduct()->getHiddenDependents())) {
                return [];
            }

            $hiddenDependentsJson = $this->option->getProduct()->getHiddenDependents();
            return $this->baseHelper->jsonDecode($hiddenDependentsJson);
        }
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
}
