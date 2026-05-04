<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionFeatures\Model\Product\Option\Value;

use Magento\Framework\App\RequestInterface as Request;
use MageWorx\OptionFeatures\Helper\Data as Helper;
use Magento\Checkout\Model\Cart;
use Magento\Framework\App\State;
use Zend\Stdlib\StringWrapper\MbString;
use Magento\Catalog\Model\Product\Option;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionBase\Helper\System as SystemHelper;
use MageWorx\OptionBase\Model\HiddenDependents as HiddenDependentsStorage;

class AdditionalHtml
{
    protected Helper $helper;
    protected Request $request;
    protected Cart $cart;
    protected State $state;
    protected \Magento\Backend\Model\Session\Quote $backendQuoteSession;
    protected PricingHelper $pricingHelper;
    protected Option $option;
    protected $optionsQty;
    protected \DOMDocument $dom;
    protected BaseHelper $baseHelper;
    protected SystemHelper $systemHelper;
    protected HiddenDependentsStorage $hiddenDependentsStorage;
    protected array $hiddenDependents = [];

    /**
     * @param Request $request
     * @param Helper $helper
     * @param State $state
     * @param Cart $cart
     * @param PricingHelper $pricingHelper
     * @param BaseHelper $baseHelper
     * @param SystemHelper $systemHelper
     * @param HiddenDependentsStorage $hiddenDependentsStorage
     */
    public function __construct(
        Request $request,
        Cart $cart,
        State $state,
        Helper $helper,
        \Magento\Backend\Model\Session\Quote $backendQuoteSession,
        PricingHelper $pricingHelper,
        BaseHelper $baseHelper,
        SystemHelper $systemHelper,
        HiddenDependentsStorage $hiddenDependentsStorage
    ) {
        $this->request                 = $request;
        $this->cart                    = $cart;
        $this->state                   = $state;
        $this->helper                  = $helper;
        $this->backendQuoteSession     = $backendQuoteSession;
        $this->pricingHelper           = $pricingHelper;
        $this->baseHelper              = $baseHelper;
        $this->systemHelper            = $systemHelper;
        $this->hiddenDependentsStorage = $hiddenDependentsStorage;
    }

    /**
     * @param \DOMDocument $dom
     * @param Option $option
     * @return void
     */
    public function getAdditionalHtml($dom, $option)
    {
        if (!$dom || !$option) {
            return;
        }
        $this->dom    = $dom;
        $this->option = $option;

        $this->preselectIsDefaults();

        if (!$this->helper->isQtyInputEnabled()) {
            return;
        }

        $this->optionsQty = $this->getQuoteItemOptionsQty();

        $body = $this->dom->documentElement->firstChild;

        if ($this->isCheckboxWithQtyInput($this->option)) {
            $this->addHtmlToMultiSelectionOption();
        } else {
            if ($this->isDropdownWithQtyInput($this->option) || $this->isRadioWithQtyInput($this->option)) {
                $qtyInput = $this->getHtmlForSingleSelectionOption();
            } elseif ($this->isMultiselect($this->option) || !$this->option->getQtyInput()) {
                $qtyInput = $this->getDefaultHtml();
            } else {
                return;
            }

            $tpl = new \DOMDocument();
            $tpl->loadHtml($qtyInput);
            $body->appendChild($this->dom->importNode($tpl->documentElement, true));
        }

        libxml_clear_errors();

        return;
    }

    /**
     * Preselect isDefaults or previously selected values, which are stored in quote items
     */
    protected function preselectIsDefaults()
    {
        if (empty($this->option->getProduct())
            || $this->systemHelper->isConfigureQuoteItemsAction()
            || $this->systemHelper->isCheckoutCartConfigureAction()
        ) {
            return;
        }

        if ($this->systemHelper->isShareableLink()) {
            $hiddenDependents = $this->hiddenDependentsStorage->getQuoteItemsHiddenDependents();
        } else {
            $hiddenDependentsJson = $this->option->getProduct()->getHiddenDependents();
            try {
                $hiddenDependents = $this->baseHelper->jsonDecode($hiddenDependentsJson);
            } catch (\Exception $exception) {
                return;
            }
        }

        if (empty($hiddenDependents)
            || !is_array($hiddenDependents)
            || empty($hiddenDependents['preselected_values'])
            || !is_array($hiddenDependents['preselected_values'])
        ) {
            return;
        }

        $this->hiddenDependents = $hiddenDependents;

        $xpath = new \DOMXPath($this->dom);

        $hasHiddenValue = (!empty($hiddenDependents)
            && is_array($hiddenDependents)
            && !empty($hiddenDependents['hidden_values'])
            && is_array($hiddenDependents['hidden_values'])
        );

        $count = 1;
        foreach ($this->option->getValues() as $value) {
            $count++;

            if (empty($hiddenDependents['preselected_values'][$value->getOptionId()])
                || !in_array(
                    $value->getOptionTypeId(),
                    array_values($hiddenDependents['preselected_values'][$value->getOptionId()])
                )
            ) {
                continue;
            }

            if ($hasHiddenValue
                && (in_array($value->getOptionTypeId(), $hiddenDependents['hidden_values'])
                    || in_array($this->option->getOptionId(), $hiddenDependents['hidden_options']))
            ) {
                continue;
            }

            if ($this->baseHelper->isCheckbox($this->option) || $this->baseHelper->isRadio($this->option)) {
                $input =
                    $xpath->query(
                        '//div/div[descendant::label[@for="options_' . $this->option->getOptionId(
                        ) . '_' . $count . '"]]//input'
                    )->item(0);
                if ($input) {
                    $input->setAttribute('checked', 'checked');
                }
            } elseif ($this->baseHelper->isDropdown($this->option) || $this->baseHelper->isMultiselect($this->option)) {
                $select =
                    $xpath->query('//option[@value="' . $value->getOptionTypeId() . '"]')->item(0);
                if ($select) {
                    $select->setAttribute('selected', '');
                }
            }
        }
    }

    protected function getInnerHtml(\DOMElement $node): string
    {
        $innerHTML = '';
        $children  = $node->childNodes;
        foreach ($children as $child) {
            $innerHTML .= $child->ownerDocument->saveXML($child);
        }

        return (string)$innerHTML;
    }

    /**
     * @param int $optionValue
     * @return string
     */
    protected function getOptionQty($optionValue): string
    {
        $qty = 0;
        if (isset($this->optionsQty[$this->option->getOptionId()])) {
            if (!is_array($this->optionsQty[$this->option->getOptionId()])) {
                $qty = $this->optionsQty[$this->option->getOptionId()];
            } else {
                if (isset($this->optionsQty[$this->option->getOptionId()][$optionValue])) {
                    $qty = $this->optionsQty[$this->option->getOptionId()][$optionValue];
                }
            }
        }
        return (string)$qty;
    }

    /**
     * Get qty input from buyRequest
     *
     * @return int
     */
    protected function getQuoteItemOptionsQty()
    {
        $optionsQty = [];
        if ($this->request->getControllerName() != 'product') {
            $quoteItemId = (int)$this->request->getParam('id');
            if ($quoteItemId) {
                if ($this->state->getAreaCode() == \Magento\Framework\App\Area::AREA_ADMINHTML) {
                    $quoteItem = $this->backendQuoteSession->getQuote()->getItemById($quoteItemId);
                } else {
                    $quoteItem = $this->cart->getQuote()->getItemById($quoteItemId);
                }
                if ($quoteItem) {
                    $buyRequest = $quoteItem->getBuyRequest();
                    if ($buyRequest) {
                        $optionsQty = $buyRequest->getOptionsQty();
                    }
                }
            }
        }
        return $optionsQty;
    }

    /**
     * Get qty input html for checkbox (multiswatch in future)
     *
     * @return void
     */
    protected function addHtmlToMultiSelectionOption()
    {
        $count = 1;
        foreach ($this->option->getValues() as $value) {
            $count++;
            $optionValueQty = $this->getOptionQty($value->getOptionTypeId());
            $optionQtyLabel = $this->getDefaultQtyLabel($this->option->getProduct()->getStoreId());
            $qtyInput       = '<div class="label-qty" style="display: inline-block; padding: 5px; margin-left: 3em">' .
                '<b>' . $this->baseHelper->getConvertEncoding($optionQtyLabel) . '</b>' .
                '<input name="options_qty[' . $this->option->getId() . '][' . $value->getOptionTypeId() . ']"' .
                ' id="options_' . $this->option->getId() . '_' . $value->getOptionTypeId() . '_qty"' .
                ' class="qty mageworx-option-qty" type="number" ';


            if (empty($this->hiddenDependents['preselected_values'][$value->getOptionId()])
                || !in_array(
                    $value->getOptionTypeId(),
                    array_values($this->hiddenDependents['preselected_values'][$value->getOptionId()])
                )
            ) {
                $qtyInput .= 'value="' . $optionValueQty . '" disabled';
            } else {
                $qty      = $optionValueQty ?: '1';
                $qtyInput .= 'value="' . $qty . '"';
            }

            $qtyInput .= ' min="0" style="width: 3em; text-align: center; vertical-align: middle;"' .
                ' data-parent-selector="options[' . $this->option->getId() . '][' . $value->getOptionTypeId() . ']"' .
                ' /></div>';

            $tpl = new \DOMDocument('1.0', 'UTF-8');
            $tpl->loadHtml($qtyInput);

            $xpath    = new \DOMXPath($this->dom);
            $idString = 'options_' . $this->option->getId() . '_' . $count;
            $input    = $xpath->query("//*[@id='$idString']")->item(0);

            $input->setAttribute('style', 'vertical-align: middle');
            $input->parentNode->appendChild($this->dom->importNode($tpl->documentElement, true));
        }
    }

    /**
     * Get qty input html for dropdown, radiobutton, swatch
     *
     * @return string
     */
    protected function getHtmlForSingleSelectionOption()
    {
        $optionQty      = $this->getOptionQty($this->option->getId());
        $optionQtyLabel = $this->getDefaultQtyLabel($this->option->getProduct()->getStoreId());
        $qtyInput = '<div class="label-qty" style="display: inline-block; padding: 5px;">'
            . '<b>' . $this->baseHelper->getConvertEncoding($optionQtyLabel) . '</b>'
            . '<input name="options_qty[' . $this->option->getId() . ']"'
            . ' id="options_' . $this->option->getId() . '_qty"' .
            ' class="qty mageworx-option-qty" type="number" ';

        if (empty($this->hiddenDependents['preselected_values'][$this->option->getId()])) {
            $qtyInput .= 'value="' . $optionQty . '" disabled';
        } else {
            $qty      = $optionQty ?: '1';
            $qtyInput .= 'value="' . $qty . '"';
        }

        $qtyInput .= ' min="0" style="width: 3em; text-align: center; vertical-align: middle;"'
            . ' data-parent-selector="options[' . $this->option->getId() . ']" />' . '</div>';

        return (string)$qtyInput;
    }

    /**
     * Get qty input html for multiselect
     *
     * @return string
     */
    protected function getDefaultHtml()
    {
        return '<input name="options_qty[' . $this->option->getId() . ']" id="options_'
            . $this->option->getId() . '_qty" class="qty mageworx-option-qty" type="hidden" value="1"'
            . ' style="width: 3em; text-align: center; vertical-align: middle;"'
            . ' data-parent-selector="options[' . $this->option->getId() . ']"/>';
    }

    /**
     * Get default qty label for specified store
     *
     * @param int $storeId
     * @return string
     */
    protected function getDefaultQtyLabel($storeId): string
    {
        return htmlspecialchars($this->helper->getDefaultQtyLabel($storeId));
    }

    /**
     * Check if option is checkbox and QtyInput is set
     *
     * @return bool
     */
    protected function isCheckboxWithQtyInput($option)
    {
        return $option->getType() == Option::OPTION_TYPE_CHECKBOX && $option->getQtyInput();
    }

    /**
     * Check if option is dropdown/swatch and QtyInput is set
     *
     * @return bool
     */
    protected function isDropdownWithQtyInput($option)
    {
        return $option->getType() == Option::OPTION_TYPE_DROP_DOWN && $option->getQtyInput();
    }

    /**
     * Check if option is radio and QtyInput is set
     *
     * @return bool
     */
    protected function isRadioWithQtyInput($option)
    {
        return $option->getType() == Option::OPTION_TYPE_RADIO && $option->getQtyInput();
    }

    /**
     * Check if option is multiselect
     *
     * @return bool
     */
    protected function isMultiselect($option)
    {
        return $option->getType() == Option::OPTION_TYPE_MULTIPLE;
    }
}
