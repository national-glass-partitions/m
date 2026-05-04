<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionBase\Plugin\Api\Product\Option;

use Magento\Catalog\Api\Data\ProductCustomOptionExtensionFactory;
use Magento\Catalog\Api\Data\ProductCustomOptionValuesExtensionFactory;
use Magento\Catalog\Model\Product\Option;
use MageWorx\OptionBase\Model\Product\Option\Attributes as OptionAttributes;
use MageWorx\OptionBase\Model\Product\Option\Value\Attributes as OptionValueAttributes;

/**
 * Class GetOptionAttributes
 *
 * @package MageWorx\OptionBase\Plugin\Api\Product\Option
 */
class GetOptionAttributes
{
    private ProductCustomOptionExtensionFactory $productCustomOptionExtensionFactory;
    private ProductCustomOptionValuesExtensionFactory $productCustomOptionValuesExtensionFactory;
    protected OptionAttributes $optionAttributes;
    protected Option $optionEntity;
    protected OptionValueAttributes $optionValueAttributes;

    /**
     * GetOptionAttributes constructor.
     *
     * @param OptionAttributes $optionAttributes
     * @param Option $optionEntity
     * @param OptionValueAttributes $optionValueAttributes
     * @param ProductCustomOptionExtensionFactory $productCustomOptionExtensionFactory
     * @param ProductCustomOptionValuesExtensionFactory $productCustomOptionValuesExtensionFactory
     */
    public function __construct(
        OptionAttributes $optionAttributes,
        Option $optionEntity,
        OptionValueAttributes $optionValueAttributes,
        ProductCustomOptionExtensionFactory $productCustomOptionExtensionFactory,
        ProductCustomOptionValuesExtensionFactory $productCustomOptionValuesExtensionFactory
    ) {
        $this->optionAttributes                          = $optionAttributes;
        $this->optionEntity                              = $optionEntity;
        $this->optionValueAttributes                     = $optionValueAttributes;
        $this->productCustomOptionExtensionFactory       = $productCustomOptionExtensionFactory;
        $this->productCustomOptionValuesExtensionFactory = $productCustomOptionValuesExtensionFactory;
    }

    /**
     * @param Option\Repository $subject
     * @param array $options
     * @return array
     */
    public function afterGetList(\Magento\Catalog\Model\Product\Option\Repository $subject, $options)
    {
        $optionAttributes      = $this->optionAttributes->getData();
        $optionValueAttributes = $this->optionValueAttributes->getData();
        foreach ($options as $option) {
            $this->addExtensionAttributes($option, $optionAttributes, $optionValueAttributes);
        }

        return $options ?: [];
    }

    /**
     * @param Option\Repository $subject
     * @param mixed $option
     * @return mixed
     */
    public function afterGet(\Magento\Catalog\Model\Product\Option\Repository $subject, $option)
    {
        $optionAttributes      = $this->optionAttributes->getData();
        $optionValueAttributes = $this->optionValueAttributes->getData();
        $this->addExtensionAttributes($option, $optionAttributes, $optionValueAttributes);

        return $option;
    }

    public function addExtensionAttributes(object $option, array $optionAttributes, array $optionValueAttributes)
    {
        /** @var ProductCustomOptionExtensionFactory $productCustomOptionExtensionFactory */
        $optionExtensionAttributes = $option->getExtensionAttributes() ??
            $this->productCustomOptionExtensionFactory->create();

        foreach ($optionAttributes as $optionAttribute) {
            $attributeName = $optionAttribute->getName();
            $optionExtensionAttributes->setData($attributeName, $option->getData($attributeName));
        }
        if (isset($option['type'])
            && $this->optionEntity->getGroupByType($option['type']) === \Magento\Catalog\Model\Product\Option::OPTION_GROUP_SELECT
        ) {
            foreach ($option->getValues() as $value) {
                /** @var ProductCustomOptionValuesExtensionFactory $productCustomOptionValuesExtensionFactory */
                $valueExtensionAttributes = $value->getExtensionAttributes() ??
                    $this->productCustomOptionValuesExtensionFactory->create();

                foreach ($optionValueAttributes as $valueAttribute) {
                    $attributeName = $valueAttribute->getName();
                    $valueExtensionAttributes->setData($attributeName, $value->getData($attributeName));
                }
            }
        }
    }
}
