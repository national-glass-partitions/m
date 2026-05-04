<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MageWorx\OptionInventory\Observer;

use \MageWorx\OptionInventory\Model\StockProvider;
use \MageWorx\OptionInventory\Model\Validator;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use \Magento\Catalog\Model\ResourceModel\Product\Option\Value\Collection as OptionValueCollection;
use \Magento\Catalog\Api\ProductRepositoryInterface;
use \Magento\CatalogInventory\Api\StockRegistryInterface;

/**
 * Prepare array with information about used option values qty
 */
class OptionValuesQty
{
    protected Validator $validator;

    /**

    /**
     * @var StockProvider
     */
    protected StockProvider $stockProvider;
    protected OptionValueCollection $valueCollection;
    protected ProductRepositoryInterface $productRepositoryInterface;
    protected StockRegistryInterface $stockRegistry;

    /**
     * OptionValuesQty constructor.
     *
     * @param Validator $validator
     * @param StockProvider $stockProvider
     * @param OptionValueCollection $valueCollection
     * @param StockRegistryInterface $stockRegistry
     * @param ProductRepositoryInterface $productRepositoryInterface
     */
    public function __construct(
        Validator $validator,
        StockProvider $stockProvider,
        OptionValueCollection $valueCollection,
        StockRegistryInterface $stockRegistry,
        ProductRepositoryInterface $productRepositoryInterface
    ) {
        $this->validator = $validator;
        $this->stockProvider = $stockProvider;
        $this->valueCollection = $valueCollection;
        $this->stockRegistry = $stockRegistry;
        $this->productRepositoryInterface = $productRepositoryInterface;
    }

    /**
     * Retrieve array of [valueId => qty] to subtract this
     *
     * @param \Magento\Framework\DataObject $requestedValues
     * @param array $originQuoteValues
     * @return array
     */
    public function getItemsToCorrect($requestedValues, $originQuoteValues)
    {
        $itemsToCorrect = [];

        foreach ($requestedValues as $key => $value) {
            if (!isset($originQuoteValues[$key])) {
                continue;
            }

            $itemType = $this->validator->getItemType($originQuoteValues[$key]);

            switch ($itemType) {
                case 'option':
                    $this->_addItemToArrayOptions($itemsToCorrect, $value);
                    break;
                case 'product':
                    $this->_addItemToArrayProducts($itemsToCorrect, $value, $originQuoteValues[$key]);
                    break;
            }
        }

        return $itemsToCorrect;
    }

    /**
     * Adds option value qty to $itemsToCorrect (creates new entry or increments existing one)
     *
     * @param array $itemsToCorrect
     * @param \Magento\Framework\DataObject $value
     * @return void
     */
    protected function _addItemToArrayOptions(&$itemsToCorrect, $value)
    {
        $optionsKey = 'options';
        $valueId = $value->getId();
        $valueQty = $value->getQty();

        if (isset($itemsToCorrect[$optionsKey][$valueId])) {
            $itemsToCorrect[$optionsKey][$valueId] += $valueQty;
        } else {
            $itemsToCorrect[$optionsKey][$valueId] = $valueQty;
        }
    }

    /**
     * Adds product qty to $itemsToCorrect['product'] (creates new entry or increments existing one)
     *
     * @param array $itemsToCorrect
     * @param \Magento\Framework\DataObject $requestedValue
     * @param array $originValue
     * @return void
     */
    protected function _addItemToArrayProducts(&$itemsToCorrect, $requestedValue, $originValue)
    {
        $productsKey = 'products';
        $product = $this->productRepositoryInterface->get($originValue['sku']);
        $productStock = $this->stockRegistry->getStockItem($product->getId(), $product->getStore()->getWebsiteId());

        if (!$productStock->getManageStock()) {
            return;
        }

        if (isset($itemsToCorrect[$productsKey][$product->getId()])) {
            $qty = $itemsToCorrect[$productsKey][$product->getId()]['qty'];
            $itemsToCorrect[$productsKey][$product->getId()]['qty'] = $qty - $requestedValue->getQty();
        } else {
            $itemsToCorrect[$productsKey][$product->getId()] = [
                'product' => $product,
                'productStock' => $productStock,
                'qty' => $productStock->getQty() - $requestedValue->getQty()
            ];
        }
    }
}
