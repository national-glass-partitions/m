<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionInventory\Block;

use Magento\Catalog\Model\Product;
use Magento\Framework\Serialize\Serializer\Json as Serializer;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use MageWorx\OptionInventory\Helper\Data as HelperInventory;
use MageWorx\OptionInventory\Helper\Stock as HelperStock;
use MageWorx\OptionBase\Helper\Data as HelperBase;

class Inventory extends Template
{
    protected Serializer $serializer;
    protected HelperInventory $helperInventory;
    protected HelperStock $helperStock;
    protected HelperBase $baseHelper;
    protected Registry $registry;
    private array $validationCache = [];

    public function __construct(
        Context $context,
        Serializer $serializer,
        HelperInventory $helperInventory,
        HelperStock $helperStock,
        HelperBase $baseHelper,
        Registry $registry,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $data
        );
        $this->serializer      = $serializer;
        $this->helperInventory = $helperInventory;
        $this->helperStock     = $helperStock;
        $this->baseHelper      = $baseHelper;
        $this->registry        = $registry;
    }

    /**
     * @return string
     */
    public function getJsonData(): string
    {
        $product = $this->getProduct();
        if (!$product || empty($product->getOptions())) {
            return '';
        }

        if (!empty($this->validationCache[$product->getId()])) {
            return $this->validationCache[$product->getId()];
        }

        if ($this->helperInventory->isDisplayOptionInventoryOnFrontend()) {
            $manageStockValuesData = $this->getManageStockValues();
        } else {
            $manageStockValuesData = [];
        }

        $data = [
            'stock_message_url'        => $this->_urlBuilder->getUrl('mageworx_optioninventory/stockmessage/update'),
            'manage_stock_values_data' => $manageStockValuesData
        ];

        return $this->validationCache[$product->getId()] = (string)$this->serializer->serialize($data);
    }

    /**
     * @return array
     */
    protected function getManageStockValues(): array
    {
        $manageStockValuesData = [];
        $options               = $this->getProduct()->getOptions();
        $optionIds             = $this->getOptionsIdsContainStockValues($options);
        $manageStockOptions    = $this->helperStock->getOptionsContainManageStockValues($optionIds);

        foreach ($options as $option) {
            $optionId = $option->getOptionId();
            if (!in_array($optionId, $manageStockOptions)) {
                continue;
            }
            
            $values = $option->getValues();
            foreach ($values as $value) {
                if ($value->getManageStock()) {
                    $manageStockValuesData[$optionId][$value->getId()] = $value->getId();
                }
            }
        }

        return $manageStockValuesData;
    }

    /**
     * @return mixed|null
     */
    protected function getProduct(): ?Product
    {
        $product = $this->registry->registry('product');
        if (!$product || !$product->getId()) {
            return null;
        }

        return $product;
    }

    /**
     * @param array $options
     * @return array
     */
    protected function getOptionsIdsContainStockValues(array $options): array
    {
        $optionIds = [];
        foreach ($options as $option) {
            if (!in_array($option->getType(), $this->baseHelper->getSelectableOptionTypes())) {
                continue;
            }

            $optionIds[] = $option->getOptionId();
        }

        return $optionIds;
    }
}
