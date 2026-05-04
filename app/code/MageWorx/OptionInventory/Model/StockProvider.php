<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionInventory\Model;

use Magento\Catalog\Api\Data\ProductCustomOptionInterface;
use Magento\Catalog\Model\Product\Option;
use Magento\Framework\DataObjectFactory;
use MageWorx\OptionInventory\Helper\Stock as StockHelper;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionInventory\Model\ResourceModel\Product\Option\Value\CollectionFactory as OptionValueCollectionFactory;
use Magento\Catalog\Model\Product\Option\ValueFactory as OptionValueFactory;
use Magento\Catalog\Model\Product\OptionFactory as OptionFactory;

/**
 * StockProvider model.
 *
 * @package MageWorx\OptionInventory\Model
 */
class StockProvider
{
    /**
     * OptionInventory Stock helper
     *
     * @var StockHelper
     */
    protected StockHelper $stockHelper;
    protected DataObjectFactory $dataObjectFactory;
    protected BaseHelper $baseHelper;
    protected OptionValueCollectionFactory $optionValueCollectionFactory;
    protected array $cachedOptions;
    protected OptionValueFactory $optionValueFactory;
    protected OptionFactory $optionFactory;

    /**
     * StockProvider constructor.
     *
     * @param StockHelper $stockHelper
     * @param DataObjectFactory $dataObjectFactory
     * @param BaseHelper $baseHelper
     * @param OptionValueCollectionFactory $optionValueCollectionFactory
     * @param OptionValueFactory $optionValueFactory
     * @param OptionFactory $optionFactory
     */
    public function __construct(
        StockHelper $stockHelper,
        DataObjectFactory $dataObjectFactory,
        BaseHelper $baseHelper,
        OptionValueCollectionFactory $optionValueCollectionFactory,
        OptionValueFactory $optionValueFactory,
        OptionFactory $optionFactory
    ) {
        $this->stockHelper                  = $stockHelper;
        $this->dataObjectFactory            = $dataObjectFactory;
        $this->baseHelper                   = $baseHelper;
        $this->optionValueCollectionFactory = $optionValueCollectionFactory;
        $this->optionValueFactory           = $optionValueFactory;
        $this->optionFactory                = $optionFactory;
    }

    /**
     * Retrieve Original option values data
     *
     * @param array $requestedData Options array
     * @return array
     */
    public function getOriginData(array $requestedData): array
    {
        $originalData = [];

        $valuesId = array_keys($requestedData);
        /** @var \MageWorx\OptionInventory\Model\ResourceModel\Product\Option\Value\Collection $valuesCollection */
        $valuesCollection = $this->optionValueCollectionFactory->create();
        $valuesCollection->getValuesByOption($valuesId);

        foreach ($valuesCollection as $value) {
            $originalData[$value->getId()] = $value;
        }

        return $originalData;
    }

    /**
     * Retrieve Requested option values data
     *
     * @param array $items Quote items array
     * @param array $cart Option array retrieved from POST
     * @return array
     */
    public function getRequestedData(array $items, array $cart): array
    {
        $requestedData = [];

        $items = !is_array($items) ? [$items] : $items;

        foreach ($items as $item) {
            $itemRequestedData = $this->getItemData($item, $cart);

            foreach ($itemRequestedData as $valueId => $valueData) {
                if (isset($requestedData[$valueId])) {
                    $value = $requestedData[$valueId];
                    $value->setQty($value->getQty() + $valueData->getQty());
                } else {
                    $requestedData[$valueId] = $valueData;
                }
            }
        }

        return $requestedData;
    }

    /**
     * Retrieve item option values data
     *
     * @param \Magento\Quote\Model\Quote\Item $item
     * @param array $cart
     * @return array
     * @throws \Exception
     */
    public function getItemData(\Magento\Quote\Model\Quote\Item $item, array $cart = []): array
    {
        $requestedData = [];

        $itemInfo            = $this->getItemInfo($item);
        $itemOptions         = isset($itemInfo['options']) ? $itemInfo['options'] : [];
        $tempOptionValueData = [];
        foreach ($itemOptions as $optionId => $values) {
            $productOption = $item->getProduct()->getOptionById($optionId);

            // skip if no option by $optionId
            if (!$productOption) {
                continue;
            }

            // skip non-selectable options
            if (empty($productOption->getValues())) {
                continue;
            }

            // Options with multiple values
            if (is_array($values)) {
                foreach ($values as $value) {
                    if (!is_array($productOption->getValues()) || !isset($productOption->getValues()[$value])) {
                        continue;
                    }
                    $isManageStock = $productOption->getValues()[$value]->getManageStock();

                    if (!$isManageStock) {
                        continue;
                    }

                    $tempOptionValueData[$value] = [
                        'option_id' => $optionId
                    ];
                }
            } else { // One-valued options
                if (!isset($productOption->getValues()[$values])) {
                    continue;
                }

                $isManageStock = $productOption->getValues()[$values]->getManageStock();

                if (!$isManageStock) {
                    continue;
                }

                $tempOptionValueData[$values] = [
                    'option_id' => $optionId
                ];
            }
        }

        foreach ($tempOptionValueData as $valueId => $valueData) {
            $qty                     = $this->baseHelper->getOptionValueQty($valueId, $valueData, $item, $cart);
            $currentProductName      = $item->getName();
            $optionData              = $item->getProduct()->getOptionById($valueData['option_id']);
            $currentOptionName       = $optionData->getTitle();
            $currentValueName        = $optionData->getValueById($valueId)->getTitle();
            $requestedData[$valueId] = $this->dataObjectFactory->create(
                [
                    'data' => [
                        'id'           => $valueId,
                        'qty'          => $qty,
                        'name'         => $currentProductName,
                        'option_title' => $currentOptionName,
                        'value_title'  => $currentValueName
                    ]
                ]
            );
        }

        return $requestedData;
    }

    /**
     * Retrieve item info
     *
     * @param \Magento\Quote\Model\Quote\Item $item Quote Item
     * @return array
     */
    protected function getItemInfo(\Magento\Quote\Model\Quote\Item $item): array
    {
        $itemOptions = $item->getOptionsByCode();

        // check if this item is simple related to the configurable product
        if (isset($itemOptions['parent_product_id'])) {
            return [];
        }

        if (!isset($itemOptions['info_buyRequest'])) {
            return [];
        }

        $itemInfoBuyRequest = $itemOptions['info_buyRequest'];

        return $this->baseHelper->decodeBuyRequestValue($itemInfoBuyRequest->getData('value'));
    }

    /**
     * This method updates options stock message
     *
     * @param array $options
     * @return array
     */
    public function updateOptionsStockMessage(array $options): array
    {
        \Magento\Framework\Profiler::start('optionInventory-stockProvider-updateOptionsStockMessage');

        $optionIds          = array_keys($options);
        $manageStockOptions = $this->stockHelper->getOptionsContainManageStockValues($optionIds);
        $optionValueIds     = $this->stockHelper->getOptionValueIds($options, $manageStockOptions);

        $hash = hash('sha256', implode(',', $optionValueIds));
        if (isset($this->cachedOptions[$hash])) {

            \Magento\Framework\Profiler::stop('optionInventory-stockProvider-updateOptionsStockMessage');

            return $this->cachedOptions[$hash];
        }

        $optionValuesCollection = $this->loadOptionValues($optionValueIds);
        $optionCollection       = $this->optionFactory->create()
                                                      ->getCollection()
                                                      ->addFieldToFilter(
                                                          'option_id',
                                                          ['in' => implode(',', $manageStockOptions)]
                                                      );
        $optionsCollection      = $optionCollection->getItems();
        $optionsToUpdate        = array_intersect_key($options, array_flip($manageStockOptions));
        foreach ($optionsToUpdate as $optionId => $values) {
            $option = $optionsCollection[$optionId];
            if (!is_object($option)) {
                continue;
            }

            if ($option->getGroupByType() == ProductCustomOptionInterface::OPTION_GROUP_SELECT) {
                foreach ($values as $valueId => $valueData) {
                    $valueModel = $this->getValueById($optionValuesCollection, (int)$valueId);
                    if ($valueModel->getManageStock()) {
                        $stockMessage = $this->stockHelper->getStockMessage($valueModel, $option->getProductId());

                        $options[$optionId][$valueId]['stockMessage'] = $stockMessage;
                    }
                }
            }
        }

        $this->cachedOptions[$hash] = $options;


        /* Front Product
         *
         * Origin code
         *
         * time - 2.025992 (2.009212)
         * avg - 2.025992 (2.009212)
         * count - 1
         * memory - 1,947,424
         * real memory - 4,194,304
         *
         * -------------------------
         *
         * Updated code
         *
         * time - 1.487559 (1.476566)
         * avg - 1.487559  (1.476566)
         * memory - 1,617,384
         * real memory - 4,194,304
         *
         */
        \Magento\Framework\Profiler::stop('optionInventory-stockProvider-updateOptionsStockMessage');

        return $options;
    }

    /**
     * Retrieve options values by ids.
     * If OptionLink module is enabled this method will return data
     * taking into account products linked by SKU to options.
     *
     * @param array $valuesId
     * @return array
     */
    protected function loadOptionValues(array $valuesId): array
    {
        /** @var \MageWorx\OptionInventory\Model\ResourceModel\Product\Option\Value\Collection $valuesCollection */
        $valuesCollection = $this->optionValueCollectionFactory->create();
        $valuesCollection->setFlag('mw_avoid_adding_attributes', true);
        $valuesCollection->getSelect()
                         ->where('main_table.option_type_id IN (?)', array_filter($valuesId, 'is_numeric'));

        return $valuesCollection->load()->getData();
    }

    /**
     * Retrieve option value by id.
     *
     * @param array $values
     * @param int $valueId
     * @return \Magento\Catalog\Model\Product\Option\Value
     */
    protected function getValueById(array $values, int $valueId): \Magento\Catalog\Model\Product\Option\Value
    {
        foreach ($values as $value) {
            if ($value['option_type_id'] == $valueId) {

                return $this->optionValueFactory->create()->setData($value);
            }
        }
    }
}
