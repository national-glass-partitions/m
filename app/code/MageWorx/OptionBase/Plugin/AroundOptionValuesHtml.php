<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionBase\Plugin;

use \Magento\Catalog\Block\Product\View\Options\Type\Select;
use MageWorx\OptionBase\Model\Product\Option\Value\AdditionalHtmlData;

/**
 * This plugin adds option_type_id to html elements.
 */
class AroundOptionValuesHtml
{
    protected AdditionalHtmlData $additionalHtmlData;
    protected \DOMXPath $xpath;
    protected string $selectedOptionValuesFlag;

    public function __construct(
        AdditionalHtmlData $additionalHtmlData
    ) {
        $this->additionalHtmlData = $additionalHtmlData;
    }

    /**
     * @param Select $subject
     * @param \Closure $proceed
     * @return string
     */
    public function aroundGetValuesHtml(Select $subject, \Closure $proceed)
    {
        $result = $proceed();
        $option = $subject->getOption();

        $dom                     = new \DOMDocument();
        $dom->preserveWhiteSpace = false;

        $result = mb_encode_numericentity($result, [0x80, 0x10FFFF, 0, ~0], 'UTF-8');

        libxml_use_internal_errors(true);
        $dom->loadHTML($result);
        libxml_clear_errors();

        foreach ($this->additionalHtmlData->getData() as $additionalHtmlItem) {
            $additionalHtmlItem->getAdditionalHtml($dom, $option);
        }

        $this->xpath = new \DOMXPath($dom);

        $count                          = 1;
        $this->selectedOptionValuesFlag = '';
        foreach ($option->getValues() as $value) {
            $count++;
            $element = $this->getOptionValueXPath($value->getId(), $option->getId(), $count);

            if ($element) {
                $element->setAttribute('data-option_type_id', $value->getOptionTypeId());
            }
        }

        $resultBody = $dom->getElementsByTagName('body')->item(0);

        return $this->getInnerHtml($resultBody);
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

    protected function getOptionValueXPath(string $valueId, string $optionId, int $count): ?\DOMNode
    {
        switch ($this->selectedOptionValuesFlag) {
            case 'select':
                return $this->getSelectXpath($valueId);
            case 'input':
                return $this->getInputXpath($optionId, $count);
            default:
                $select = $this->getSelectXpath($valueId);
                $input  = $this->getInputXpath($optionId, $count);

                $this->selectedOptionValuesFlag = $select ? 'select' : 'input';

                return $select ?: $input;
        }
    }

    protected function getSelectXpath(string $valueId): ?\DOMNode
    {
        return $this->xpath->query('//option[@value="' . $valueId . '"]')->item(0);
    }

    protected function getInputXpath(string $optionId, int $count): ?\DOMNode
    {
        return $this->xpath->query('//div/div[descendant::label[@for="options_' . $optionId . '_' . $count . '"]]')
                           ->item(0);
    }
}
