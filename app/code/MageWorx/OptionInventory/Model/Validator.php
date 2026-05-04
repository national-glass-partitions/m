<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionInventory\Model;

use Magento\Catalog\Api\Data\ProductCustomOptionInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductOptionInterface;
use Magento\Catalog\Model\Product\Option;
use Magento\Catalog\Model\Product\Option\Type\DefaultType;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use MageWorx\OptionBase\Api\Data\ProductCustomOptionValuesInterface;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionInventory\Helper\Data as HelperData;
use MageWorx\OptionInventory\Helper\Stock;
use MageWorx\OptionInventory\Model\ResourceModel\Product\Option\Value\Collection;
use MageWorx\OptionInventory\Model\ResourceModel\Product\Option\Value\CollectionFactory as OptionValueCollectionFactory;

/**
 * Validator model
 *
 * @package MageWorx\OptionInventory\Model
 */
class Validator extends AbstractModel
{
    protected ObjectManagerInterface $objectManager;
    protected Stock $stockHelper;
    protected OptionValueCollectionFactory $optionValueCollectionFactory;
    protected HelperData $helperData;
    protected BaseHelper $baseHelper;

    /**
     * Validator constructor.
     *
     * @param ObjectManagerInterface $objectManager
     * @param Context $context
     * @param Registry $registry
     * @param Stock $stockHelper
     * @param OptionValueCollectionFactory $optionValueCollectionFactory
     * @param HelperData $helperData
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        Context $context,
        Registry $registry,
        Stock $stockHelper,
        OptionValueCollectionFactory $optionValueCollectionFactory,
        HelperData $helperData,
        BaseHelper $baseHelper,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->objectManager                = $objectManager;
        $this->stockHelper                  = $stockHelper;
        $this->optionValueCollectionFactory = $optionValueCollectionFactory;
        $this->helperData                   = $helperData;
        $this->baseHelper                   = $baseHelper;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Validate Requested with Original data
     *
     * @param array $requestedData Requested Option Values
     * @param array $originData Original Option Values
     * @throws LocalizedException
     */
    public function validate(array $requestedData, array $originData): void
    {
        foreach ($requestedData as $requestedValue) {
            $originValue = isset($originData[$requestedValue->getId()]) ? $originData[$requestedValue->getId()] : null;
            if (!$this->isAllow($requestedValue, $originValue)) {
                $this->addError($originValue, $requestedValue);
            }
        }
    }

    /**
     * Check if allow original qty add requested qty
     *
     * @param DataObject $requestedValue
     * @param ProductCustomOptionValuesInterface $originValue
     * @return bool
     */
    protected function isAllow(
        DataObject $requestedValue,
        ProductCustomOptionValuesInterface $originValue
    ): bool {
        if (!$originValue) {
            return true;
        }

        if (!$originValue->getManageStock()) {
            return true;
        }

        if ($originValue->getQty() <= 0) {
            return false;
        }

        if ($requestedValue->getQty() > $originValue->getQty()) {
            return false;
        }

        return true;
    }

    /**
     * Throw exception
     *
     * @param ProductCustomOptionValuesInterface $value
     * @param DataObject $requestedValue
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function addError(
        ProductCustomOptionValuesInterface $value,
        DataObject $requestedValue
    ): void {
        $this->correctData($value);

        if ($value->getProductId()) {
            $formattedQty = $this->stockHelper->isfloatingQty((int)$value->getProductId())
                ? (float)$value->getQty()
                : (int)$value->getQty();
        } else {
            $formattedQty = $value->getQty();
        }
        $e = new LocalizedException(
            __(
                'We don\'t have as many  "%1" : "%2" - "%3"  as you requested (available qty: "%4").',
                $requestedValue->getName(),
                $requestedValue->getOptionTitle(),
                $requestedValue->getValueTitle(),
                $formattedQty
            )
        );
        throw $e;
    }

    /**
     * Correct some option value fields.
     * For example: 'title' - can be origin or use product name linked by sku.
     *
     * SkuIsValid - this property set the OptionLink module.
     *
     * @param ProductCustomOptionValuesInterface $value
     */
    protected function correctData(ProductCustomOptionValuesInterface $value): void
    {
        if ($value->getSkuIsValid()) {
            /** @var Collection $valuesCollection */
            $valuesCollection = $this->optionValueCollectionFactory->create();

            $valuesCollection
                ->addTitleToResult(1)
                ->getValuesByOption($value->getId());

            $item = $valuesCollection->getFirstItem();
            $value->setValueTitle($item->getTitle());
        }
    }

    /**
     * This function checks from where to take away quantity.
     *
     * @param ProductCustomOptionValuesInterface $value
     * @return string
     */
    public function getItemType(ProductCustomOptionValuesInterface $value): string
    {
        $optionType  = 'option';
        $productType = 'product';

        if (!isset($value['sku_is_valid'])) {
            return $optionType;
        }

        $skuIsValid = $value['sku_is_valid'];

        if ($skuIsValid) {
            return $productType;
        }

        return $optionType;
    }

    /**
     * Run validation process for add to cart action
     *
     * @param DataObject $subject
     * @param array $values
     * @return bool
     */
    public function canValidateAddToCart(
        DataObject $subject,
        array $values
    ): bool {
        return $this->process($subject->getOption());
    }

    /**
     * Run validation process for cart and checkout
     *
     * @param ProductInterface $product
     * @param ProductOptionInterface
     * @return bool
     */
    public function canValidateCartCheckout(ProductInterface $product, ProductCustomOptionInterface $option): bool
    {
        $product = $this->baseHelper->getInfoBuyRequest($product);
        if (!$product) {
            return true;
        }

        return $this->process($option);
    }

    /**
     * Check out of stock option values, if display out of stok is hidden - skip validation
     *
     * @param ProductCustomOptionInterface $option
     */
    protected function process(ProductCustomOptionInterface $option): bool
    {

        if ($this->helperData->isDisplayOutOfStockOptions()) {
            return true;
        }

        if ($option->getValues()) {
            foreach ($option->getValues() as $value) {
                if (!$this->stockHelper->isOutOfStockOption($value)) {

                    return true;
                }
            }
        }

        return false;
    }
}
