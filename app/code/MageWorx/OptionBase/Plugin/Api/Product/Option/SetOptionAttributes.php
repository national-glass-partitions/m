<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionBase\Plugin\Api\Product\Option;

use Magento\Catalog\Model\Product\Option;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Store\Model\Store;
use MageWorx\OptionBase\Model\Product\Option\Attributes as OptionAttributes;
use MageWorx\OptionBase\Model\Product\Option\Value\Attributes as OptionValueAttributes;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;

/**
 * Class SetOptionAttributes
 *
 * @package MageWorx\OptionBase\Plugin\Api\Product\Option
 */
class SetOptionAttributes
{
    protected ManagerInterface $eventManager;
    protected OptionAttributes $optionAttributes;
    protected Option $optionEntity;
    protected OptionValueAttributes $optionValueAttributes;
    protected ProductCollectionFactory $productCollectionFactory;

    /**
     * SetOptionAttributes constructor.
     *
     * @param OptionAttributes $optionAttributes
     * @param Option $optionEntity
     * @param OptionValueAttributes $optionValueAttributes
     * @param ManagerInterface $eventManager
     * @param ProductCollectionFactory $productCollectionFactory
     */
    public function __construct(
        OptionAttributes $optionAttributes,
        Option $optionEntity,
        OptionValueAttributes $optionValueAttributes,
        ManagerInterface $eventManager,
        ProductCollectionFactory $productCollectionFactory
    ) {
        $this->optionAttributes          = $optionAttributes;
        $this->optionEntity              = $optionEntity;
        $this->optionValueAttributes     = $optionValueAttributes;
        $this->eventManager              = $eventManager;
        $this->productCollectionFactory  = $productCollectionFactory;
    }

    /**
     * @param Option\Repository $subject
     * @param $result
     * @param \Magento\Catalog\Api\Data\ProductCustomOptionInterface $option
     * @return \Magento\Catalog\Api\Data\ProductCustomOptionInterface
     * @throws CouldNotSaveException
     */
    public function afterSave(
        \Magento\Catalog\Model\Product\Option\Repository $subject,
        $result,
        \Magento\Catalog\Api\Data\ProductCustomOptionInterface $option
    ) {
        if (!$option->getOptionId()) {
            return $result;
        }

        $optionExtensionAttr = $option->getExtensionAttributes()->__toArray();
        if (!$optionExtensionAttr) {
            return $result;
        }

        $productSku = $option->getProductSku();
        if (!$productSku) {
            throw new CouldNotSaveException(__('The product SKU is empty. Set the product SKU and try again.'));
        }

        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection */
        $collection = $this->productCollectionFactory->create();
        $collection->addStoreFilter(Store::DEFAULT_STORE_ID)
                   ->setStoreId(Store::DEFAULT_STORE_ID)
                   ->addFieldToFilter('sku', $productSku)
                   ->addOptionsToResult()
                   ->setPageSize(1);
        $product               = $collection->getFirstItem();
        $optionAttributes      = $this->optionAttributes->getData();
        $optionValueAttributes = $this->optionValueAttributes->getData();
        $originalOption        = $product->getOptionById($option->getOptionId());
        $originalOptionValues  = $originalOption->getValues();
        foreach ($optionAttributes as $optionAttribute) {
            $attributeName = $optionAttribute->getName();
            if (isset($optionExtensionAttr[$attributeName])) {
                $originalOption->setData($attributeName, $optionExtensionAttr[$attributeName]);
            }
        }
        if (isset($option['type'])
            && $this->optionEntity->getGroupByType($option['type']) === \Magento\Catalog\Model\Product\Option::OPTION_GROUP_SELECT
        ) {
            $valueExtensionAttributesArray = [];
            foreach ($option->getValues() as $value) {
                $valueExtensionAttributesArray[] = $value->getExtensionAttributes()->__toArray();
            }
            $counter = 0;
            foreach ($originalOptionValues as $originalOptionValue) {
                foreach ($optionValueAttributes as $valueAttribute) {
                    $attributeName             = $valueAttribute->getName();
                    $currentExtensionAttribute = $valueExtensionAttributesArray[$counter];
                    if (isset($currentExtensionAttribute[$attributeName])) {
                        $originalOptionValue->setData($attributeName, $currentExtensionAttribute[$attributeName]);
                    }
                }
                $counter += 1;
            }
            $originalOption->setData('values', $originalOptionValues);
        }
        $originalOption->save();
        $this->eventManager->dispatch(
            'mageworx_attributes_save_trigger',
            ['product' => $product, 'is_after_template' => false]
        );

        return $result;
    }
}
