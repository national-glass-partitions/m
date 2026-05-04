<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionInventory\Helper;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product as ProductModel;
use \Magento\CatalogInventory\Api\StockRegistryInterface as StockRegistry;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionInventory\Model\Product\LinkedAttributes as LinkedAttributes;
use MageWorx\OptionInventory\Model\ResourceModel\Product\Option\ManageStockOptionCollection;

/**
 * OptionInventory Stock Helper.
 *
 * @package MageWorx\OptionInventory\Helper
 */
class Stock extends AbstractHelper
{
    const MANAGE_STOCK_ENABLED  = '1';
    const MANAGE_STOCK_DISABLED = '0';

    protected ProductModel $product;
    protected StockRegistry $stockRegistry;
    protected Data $helperData;
    protected BaseHelper $baseHelper;
    protected LinkedAttributes $linkedAttributes;
    protected ProductRepositoryInterface $productRepository;
    protected ManageStockOptionCollection $manageStockOptionCollection;

    public function __construct(
        Data $helperData,
        ProductModel $product,
        StockRegistry $stockRegistry,
        Context $context,
        BaseHelper $baseHelper,
        LinkedAttributes $linkedAttributes,
        ProductRepositoryInterface $productRepository,
        ManageStockOptionCollection $manageStockOptionCollection
    ) {
        $this->helperData        = $helperData;
        $this->product           = $product;
        $this->stockRegistry     = $stockRegistry;
        $this->baseHelper        = $baseHelper;
        $this->linkedAttributes  = $linkedAttributes;
        $this->productRepository = $productRepository;
        $this->manageStockOptionCollection = $manageStockOptionCollection;
        parent::__construct($context);
    }

    /**
     * Check if option value is out of stock
     *
     * @param \Magento\Catalog\Api\Data\ProductCustomOptionValuesInterface $value
     * @return bool
     */
    public function isOutOfStockOption(\Magento\Catalog\Api\Data\ProductCustomOptionValuesInterface $value): bool
    {
        $manageStock = $value->getManageStock();
        $qty         = $value->getQty();


        if (!$manageStock) {
            return false;
        }

        if ($qty <= 0) {
            return true;
        }

        return false;
    }

    /**
     * @param int $productId
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isfloatingQty(int $productId): bool
    {
        if (!$productId) {
            return true;
        }

        if (!$this->product) {
            $this->productRepository->getById($productId);
        }

        $stockData = $this->product->getStockData();

        if ($stockData) {
            $isQtyDecimal = is_array($stockData)
                ? (bool)$stockData['is_qty_decimal']
                : (bool)$stockData->getIsQtyDecimal();
        } else {
            $stockData = $this->stockRegistry->getStockItem(
                $this->product->getId(),
                $this->product->getStore()->getWebsiteId()
            );
            $this->product->setStockData($stockData);

            $isQtyDecimal = (bool)$stockData->getIsQtyDecimal();
        }

        return $isQtyDecimal ?: false;
    }

    /**
     * Set stock message to xpath element
     *
     * @param \DOMDocument $dom
     * @param \DOMElement $elementTitle
     * @param string $stockMessage
     */
    public function setStockMessage(\DOMDocument $dom, \DOMElement $elementTitle, string $stockMessage = ''): void
    {
        $elementTitle->nodeValue = htmlentities($elementTitle->nodeValue . $stockMessage);
    }

    /**
     * Retrieve stock message
     *
     * @param \Magento\Catalog\Api\Data\ProductCustomOptionValuesInterface $value
     * @return string
     */
    public function getStockMessage(
        \Magento\Catalog\Api\Data\ProductCustomOptionValuesInterface $value,
        string $productId
    ): string {
        $stockMessage = '';

        $isDisplayOptionInventory   = $this->helperData->isDisplayOptionInventoryOnFrontend();
        $isDisplayOutOfStockMessage = $this->helperData->isDisplayOutOfStockMessage();

        $formattedQty = $this->isfloatingQty((int)$productId) ? (float)$value->getQty() : (int)$value->getQty();

        $inventoryMessage  = '(' . $formattedQty . ')';
        $outOfStockMessage = '(' . __('Out Of Stock') . ')';

        if ($isDisplayOutOfStockMessage) {
            $stockMessage .= !$this->isOutOfStockOption($value) && $isDisplayOptionInventory ? $inventoryMessage : '';
            $stockMessage .= $this->isOutOfStockOption($value) ? $outOfStockMessage : '';
        } else {
            $stockMessage .= $isDisplayOptionInventory ? $inventoryMessage : '';
        }

        return (string)$stockMessage;
    }

    /**
     * Disable option value
     *
     * @param \DOMElement $element
     */
    public function disableOutOfStockOption(\DOMElement $element): void
    {
        if ($element) {
            $element->setAttribute('disabled', 'disabled');
        }
    }

    /**
     * Hide option value
     *
     * @param \DOMElement $element
     */
    public function hideOutOfStockOption(\DOMElement $element): void
    {
        if ($element) {
            $element->parentNode->removeChild($element);
        }
    }

    /**
     * Retrieve options values id from requested data
     *
     * @param array $options
     * @return array
     */
    public function getRequestedValuesId(array $options): array
    {
        $valuesId = [];

        array_walk_recursive(
            $options,
            function ($value, $key) use (&$valuesId) {
                if ($value) {
                    $valuesId[] = $value;
                }
            }
        );

        return $valuesId;
    }

    /**
     * Retrieve options values id from product options
     *
     * @param array $options
     * @param array $manageStockOptions
     * @return array
     */
    public function getOptionValueIds(array $options, $manageStockOptions): array
    {
        $optionValueIds = [];

        foreach ($options as $optionId => $values) {
            if (!in_array($optionId, $manageStockOptions)) {
                continue;
            }

            if (!is_array($values)) {
                $values = [$values => []];
            }
            $optionValueIds = array_merge($optionValueIds, array_keys($values));
        }

        return $optionValueIds;
    }

    /**
     * Linked qty validator (from OptionLink)
     *
     * @return bool
     */
    public function validateLinkedQtyField(): bool
    {
        $linkedAttributesData = $this->linkedAttributes->getData('linkedAttributes');
        if (!$linkedAttributesData) {
            return false;
        }

        $linkedFields = $linkedAttributesData->getConvertedAttributesToFields();
        if (!in_array('qty', $linkedFields) ||
            !$this->baseHelper->isModuleEnabled('Magento_InventorySalesAdminUi')
        ) {
            return false;
        }

        return true;
    }

    /**
     * Gat data with option Ids which contain manage_stock values
     *
     * @param array $optionIds
     * @return array
     */
    public function getOptionsContainManageStockValues(array $optionIds): array
    {
        return $this->manageStockOptionCollection->getOptionsContainsManageStockValues($optionIds);
    }
}
