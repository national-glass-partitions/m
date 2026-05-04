<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionBase\Model;

use MageWorx\OptionBase\Helper\Data as BaseHelper;

class HiddenDependents
{
    protected array $quoteItemsHiddenDependents = [];
    protected BaseHelper $baseHelper;

    public function __construct(
        BaseHelper $baseHelper
    ) {
        $this->baseHelper   = $baseHelper;
    }

    /**
     * Get quote items hidden dependents
     *
     * @return array
     */
    public function getQuoteItemsHiddenDependents()
    {
        return $this->quoteItemsHiddenDependents;
    }

    /**
     * Set quote items hidden dependents
     *
     * @param array
     * @return void
     */
    public function setQuoteItemsHiddenDependents($data)
    {
        $this->quoteItemsHiddenDependents = $data;
    }

    /**
     * Get hidden values
     *
     * @param array
     * @return array
     */
    public function getHiddenValues($product)
    {
        try {
            $hiddenDependents = $this->getHiddenDependents($product);
        } catch (\Exception $exception) {
            return [];
        }

        if (empty($hiddenDependents)
            || empty($hiddenDependents['hidden_values'])
            || !is_array($hiddenDependents['hidden_values'])
        ) {
            return [];
        }

        return $hiddenDependents['hidden_values'];
    }

    /**
     * Get hidden options
     *
     * @param array
     * @return array
     */
    public function getHiddenOptions($product)
    {
        try {
            $hiddenDependents = $this->getHiddenDependents($product);
        } catch (\Exception $exception) {
            return [];
        }

        if (empty($hiddenDependents)
            || empty($hiddenDependents['hidden_options'])
            || !is_array($hiddenDependents['hidden_options'])
        ) {
            return [];
        }

        return $hiddenDependents['hidden_options'];
    }

    /**
     * Get hidden values
     *
     * @param array
     * @return array
     */
    protected function getHiddenDependents($product)
    {
        if ($this->baseHelper->isConfigureQuoteItemsAction()
            || $this->baseHelper->isCheckoutCartConfigureAction()
            || $this->baseHelper->isShareableLink()
        ) {
            return $this->getQuoteItemsHiddenDependents();
        } else {
            if (empty($product->getHiddenDependents())) {
                return [];
            }

            $hiddenDependentsJson = $product->getHiddenDependents();
            return $hiddenDependents = $this->baseHelper->jsonDecode($hiddenDependentsJson);
        }
    }
}
