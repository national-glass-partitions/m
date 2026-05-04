<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionDependency\Model\Product\Option;

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
            || empty($hiddenDependents['hidden_options'])
            || !is_array($hiddenDependents['hidden_options'])
        ) {
            return;
        }

        if (in_array($this->option->getOptionId(), $hiddenDependents['hidden_options'])) {
            $xpath          = new \DOMXPath($this->dom);
            $optionCssStyle = $xpath->query('//div')->item(0)->getAttribute('style') ?: '';
            $xpath->query('//div')
                  ->item(0)
                  ->setAttribute('style', 'display: none;' . $optionCssStyle);

            $dateTimeOptionTypes = [Option::OPTION_TYPE_DATE, Option::OPTION_TYPE_DATE_TIME, Option::OPTION_TYPE_TIME];
            if (in_array($this->option->getType(), $dateTimeOptionTypes) && $this->option->getIsRequire()) {
                $this->setClassForSkippingDateValidation($xpath);
            }
        }
    }

    /**
     * Set css-class for skipping date/time/datetime validation on frontend
     *
     * @param \DOMXPath $xpath
     * @return void
     */
    protected function setClassForSkippingDateValidation($xpath)
    {
        $optionCssClasses = $xpath->query('//div')->item(0)->getAttribute('class');
        $optionCssClasses .= ' mageworx-disable-date-validation';
        $xpath->query('//div')
              ->item(0)
              ->setAttribute('class', $optionCssClasses);
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
