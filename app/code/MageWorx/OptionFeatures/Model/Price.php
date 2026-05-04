<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionFeatures\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Option\Value;
use Magento\Framework\DataObject;
use Magento\Framework\Model\AbstractModel;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionBase\Helper\Price as BasePriceHelper;
use Magento\Framework\Event\ManagerInterface;
use MageWorx\OptionFeatures\Model\ResourceModel\BundleSelected;

class Price extends DataObject
{
    protected ProductRepositoryInterface $productRepository;
    protected DataObject $specialPriceModel;
    protected DataObject $tierPriceModel;
    protected BaseHelper $baseHelper;
    protected BasePriceHelper $basePriceHelper;

    /**
     * Core event manager proxy
     *
     * @var ManagerInterface
     */
    protected ManagerInterface $eventManager;
    protected BundleSelected $bundleSelected;
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    /**
     * Price constructor.
     *
     * @param ProductRepositoryInterface $productRepository
     * @param DataObject $specialPriceModel
     * @param DataObject $tierPriceModel
     * @param ManagerInterface $eventManager
     * @param BaseHelper $baseHelper
     * @param BasePriceHelper $basePriceHelper
     * @param \Magento\Framework\ObjectManagerInterface $objectmanager
     * @param BundleSelected $bundleSelected
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        DataObject $specialPriceModel,
        DataObject $tierPriceModel,
        ManagerInterface $eventManager,
        BaseHelper $baseHelper,
        BasePriceHelper $basePriceHelper,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        BundleSelected $bundleSelected
    ) {
        $this->productRepository = $productRepository;
        $this->specialPriceModel = $specialPriceModel;
        $this->tierPriceModel    = $tierPriceModel;
        $this->baseHelper        = $baseHelper;
        $this->eventManager      = $eventManager;
        $this->basePriceHelper   = $basePriceHelper;
        $this->objectManager     = $objectManager;
        $this->bundleSelected    = $bundleSelected;
        parent::__construct();
    }

    /**
     * Get actual price using suitable special and tier prices
     *
     * @param \Magento\Catalog\Model\Product\Option $option
     * @param \Magento\Catalog\Model\Product\Option\Value $value
     * @return float|null
     */
    public function getPrice($option, $value)
    {
        if (!($this->specialPriceModel instanceof AbstractModel)
            || !($this->tierPriceModel instanceof AbstractModel)
        ) {
            return $value->getPrice(true);
        }

        $originalProduct = $option->getProduct();
        $infoBuyRequest  = $this->baseHelper->getInfoBuyRequest($originalProduct);

        $valueQty   = $this->getValueQty($option, $value, $infoBuyRequest);
        $productQty = $this->getProductQty();
        if (empty($productQty)) {
            $productQty = !empty($infoBuyRequest['qty']) ? $infoBuyRequest['qty'] : 1;
        }

        $originalProductOptions = $originalProduct->getData('options');
        foreach ($originalProductOptions as $originalProductOption) {
            $originalProductOptionValues = $originalProductOption->getValues();
            if (!empty($originalProductOptionValues[$value->getOptionTypeId()])) {
                $originalValue = $originalProductOptionValues[$value->getOptionTypeId()];
                break;
            }
        }
        if (empty($originalValue)) {
            return $value->getPrice(true);
        }

        $specialPrice         = $this->specialPriceModel->getActualSpecialPrice($originalValue);
        $tierPrices           = $this->tierPriceModel->getSuitableTierPrices($originalValue);
        $suitableTierPrice    = null;
        $suitableTierPriceQty = null;

        $isOneTime = $option->getData('one_time');
        if ($isOneTime) {
            $totalQty = $valueQty;
        } else {
            $totalQty = $productQty * $valueQty;
        }

        /**
         * Without specifying the type we get Implicit conversion of a float number to an integer number
         * Eg. 0.5 -> 0, 1.3 -> 1
         */
        if (!isset($tierPrices[(string)$totalQty])) {
            foreach ($tierPrices as $tierPriceItemQty => $tierPriceItem) {
                if ($suitableTierPriceQty < $tierPriceItemQty && $totalQty >= $tierPriceItemQty) {
                    $suitableTierPrice    = $tierPriceItem;
                    $suitableTierPriceQty = $tierPriceItemQty;
                }
            }
        } else {
            $suitableTierPrice = $tierPrices[(string)$totalQty];
        }

        $actualTierPrice = isset($suitableTierPrice['price']) ? $suitableTierPrice['price'] : null;

        if ($suitableTierPrice && ($actualTierPrice < $specialPrice || $specialPrice === null)) {
            $price = $actualTierPrice;
        } elseif ($specialPrice !== null) {
            $price = $specialPrice;
        } else {
            if ($originalValue->getPriceType() == 'percent') {
                $productFinalPrice = $originalProduct->getPriceModel()->getBasePrice($originalProduct, $totalQty);
                $originalProduct->setFinalPrice($productFinalPrice);
                $this->eventManager->dispatch(
                    'catalog_product_get_final_price',
                    ['product' => $originalProduct, 'qty' => $totalQty]
                );
                $productFinalPrice = $originalProduct->getData('final_price');

                $price = $productFinalPrice * $originalValue->getPrice() / 100;
            } else {
                $price = $originalValue->getPrice();
            }
        }
        
        return $price;
    }

    /**
     * Get selected value qty
     *
     * @param \Magento\Catalog\Model\Product\Option $option
     * @param \Magento\Catalog\Model\Product\Option\Value $value
     * @param array $infoBuyRequest
     * @return float
     */
    protected function getValueQty($option, $value, $infoBuyRequest)
    {
        $valueQty = 1;
        if (!empty($infoBuyRequest['options_qty'][$option->getOptionId()][$value->getOptionTypeId()])) {
            $valueQty = $infoBuyRequest['options_qty'][$option->getOptionId()][$value->getOptionTypeId()];
        } elseif (!empty($infoBuyRequest['options_qty'][$option->getOptionId()])) {
            $valueQty = $infoBuyRequest['options_qty'][$option->getOptionId()];
        }

        return $valueQty;
    }

    public function getBundleTotalPrice(ProductInterface $product): float
    {
        $buyRequest              = $this->baseHelper->getInfoBuyRequest($product);
        $selectedIds             = '';
        $bundleProductPriceTotal = 0;

        if (!isset($buyRequest['bundle_option'])) {
            return $bundleProductPriceTotal;
        }

        foreach ($buyRequest['bundle_option'] as $selectionIds) {
            if (!is_array($selectionIds)) {
                $selectionIds = [$selectionIds];
            }
            foreach ($selectionIds as $id) {
                $selectedIds .= ', ' . $id;
            }
        }

        $selectedIds = substr($selectedIds, 1);
        $valueData   = $this->bundleSelected->getBundleSelectedData($selectedIds);
        foreach ($valueData as $key => $value) {
            $result = $value['selection_price_value'];
            if (isset($buyRequest['bundle_option_qty'])
                && isset($buyRequest['bundle_option_qty'][$value['option_id']])
            ) {
                $result *= (float)$buyRequest['bundle_option_qty'][$value['option_id']];
            } elseif (!$value['selection_can_change_qty']) {
                $result *= (float)$value['selection_qty'];
            }
            $bundleProductPriceTotal += $result;
        }

        return $bundleProductPriceTotal;
    }
}
