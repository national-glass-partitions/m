<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionSkuPolicy\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductOptionInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Event\Manager;
use Magento\Quote\Model\Quote\Item;
use MageWorx\OptionSkuPolicy\Helper\Data as Helper;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionBase\Helper\System as SystemHelper;
use MageWorx\OptionFeatures\Helper\Data as HelperFeatures;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\App\Request\Http as Request;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use MageWorx\OptionFeatures\Model\Price as ModelPrice;
use Magento\Framework\Pricing\PriceCurrencyInterface;

class SkuPolicy
{
    protected Helper $helper;
    protected BaseHelper $baseHelper;
    protected SystemHelper $systemHelper;
    protected HelperFeatures $helperFeatures;
    protected PriceCurrencyInterface $priceCurrency;
    protected ProductRepositoryInterface $productRepository;
    protected DataObjectFactory $dataObjectFactory;
    protected ProductInterface $originalProduct;
    protected ProductInterface $quoteProduct;
    protected bool $isItemChanged;
    protected bool $isItemRemoved;
    protected \Magento\Framework\DataObject $buyRequest;
    protected bool $isGroupedSkuPolicyOnly;
    protected string $productSkuPolicy;
    protected bool $toCart;
    protected Item $quoteItem;
    protected array $skuArray;
    protected Request $request;
    protected array $newQuoteItems = [];
    protected Configurable $configurableEntity;
    protected ModelPrice $modelPrice;
    protected bool $isSubmitQuoteFlag = false;
    protected Manager $eventManager;
    protected bool $isAllOptionsPolicyStandard = true;

    /**
     * SkuPolicy constructor.
     *
     * @param Helper $helper
     * @param BaseHelper $baseHelper
     * @param SystemHelper $systemHelper
     * @param HelperFeatures $helperFeatures
     * @param PriceCurrencyInterface $priceCurrency
     * @param DataObjectFactory $dataObjectFactory
     * @param Request $request
     * @param Configurable $configurableEntity
     * @param ProductRepositoryInterface $productRepository
     * @param ModelPrice $modelPrice
     * @param Manager $eventManager
     */
    public function __construct(
        Helper $helper,
        BaseHelper $baseHelper,
        SystemHelper $systemHelper,
        HelperFeatures $helperFeatures,
        PriceCurrencyInterface $priceCurrency,
        DataObjectFactory $dataObjectFactory,
        Request $request,
        Configurable $configurableEntity,
        ProductRepositoryInterface $productRepository,
        ModelPrice $modelPrice,
        Manager $eventManager
    ) {
        $this->helper             = $helper;
        $this->baseHelper         = $baseHelper;
        $this->systemHelper       = $systemHelper;
        $this->helperFeatures     = $helperFeatures;
        $this->priceCurrency      = $priceCurrency;
        $this->dataObjectFactory  = $dataObjectFactory;
        $this->productRepository  = $productRepository;
        $this->request            = $request;
        $this->configurableEntity = $configurableEntity;
        $this->modelPrice         = $modelPrice;
        $this->eventManager       = $eventManager;
    }

    /**
     * Apply SKU policy to shopping cart
     *
     * @param \Magento\Quote\Model\Quote\Item[] $quoteItems
     * @return void
     */
    public function applySkuPolicyToCart($quoteItems)
    {
        $this->toCart = true;
        $this->applySkuPolicy($quoteItems);
    }

    /**
     * Apply SKU policy to order
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment
     * @return void
     */
    public function applySkuPolicyToOrder($quote, $shippingAssignment)
    {
        if ($this->systemHelper->isEditingByOrderEditor()) {
            $quote->setCanApplySkuPolicyToOrder(true);
        }

        if (!$quote->getCanApplySkuPolicyToOrder()) {
            return;
        }

        $quoteItems = $quote->getAllItems();
        if ($this->out($quoteItems, $shippingAssignment)) {
            return;
        }

        $this->toCart = false;
        $this->applySkuPolicy($quoteItems);
        $shippingAssignment->setItems($this->newQuoteItems);
        $this->newQuoteItems = [];
    }

    /**
     * Apply SKU policy to quote items
     *
     * @param \Magento\Quote\Model\Quote\Item[] $quoteItems
     * @return void
     */
    protected function applySkuPolicy($quoteItems)
    {
        $defaultSkuPolicy = $this->helper->getDefaultSkuPolicy();

        $quote = null;
        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        foreach ($quoteItems as $quoteItem) {
            if ($quoteItem->getIsSkuPolicyApplied() || $quoteItem->getQuote()->getIsProcessingOptions()) {
                continue;
            }

            $originalProduct = $this->productRepository->getById($quoteItem->getProductId());
            $quoteProduct    = $quoteItem->getProduct();
            if (!$this->hasOptions($originalProduct, $quoteProduct)) {
                $this->newQuoteItems[] = $quoteItem;
                continue;
            }

            $this->originalProduct = $originalProduct;
            $this->quoteProduct    = $quoteProduct;
            $this->quoteItem       = $quoteItem;
            $this->buyRequest      = $this->quoteItem->getBuyRequest();
            $this->isItemChanged   = false;
            $this->isItemRemoved   = false;
            $this->skuArray        = [];

            $this->addProductSku();

            if (!$quote) {
                $quote = $quoteItem->getQuote();
            }

            if ($this->quoteProduct->getSkuPolicy() !== Helper::SKU_POLICY_USE_CONFIG
                && $this->quoteProduct->getSkuPolicy()
            ) {
                $this->productSkuPolicy = (string)$this->quoteProduct->getSkuPolicy();
            } else {
                $this->productSkuPolicy = $defaultSkuPolicy;
            }

            /** @var array $options */
            $options = $this->buyRequest->getOptions();
            if (!$options) {
                $this->newQuoteItems[] = $quoteItem;
                continue;
            }

            $this->checkSkuPolicyGroupOnly($options);
            $this->processBuyRequestOptions($options);
            $this->addCustomSkuToBuyRequest();

            if (!$this->systemHelper->isEditingByOrderEditor()) {
                $this->saveNewQuoteItemOptions($quote);
                $this->implodeQuoteSku($this->skuArray);
                $this->modifyQuoteItem();
            } else {
                $this->implodeQuoteSku($this->skuArray);
            }

            $this->quoteItem->setIsSkuPolicyApplied(true);
            if (!$this->isItemRemoved) {
                $this->newQuoteItems[] = $this->quoteItem;
            }
        }

        if (!$this->isAllOptionsPolicyStandard) {
            $this->processQuote($quote);
        }


    }

    protected function implodeQuoteSku($skuArray)
    {
        $this->quoteItem->setSku(implode('-', $skuArray));
    }

    /**
     * Add product SKU to result SKU array from original product (from child product for configurable)
     *
     * @return void
     */
    protected function addProductSku()
    {
        if ($this->quoteItem->getProductType() == 'configurable') {
            $superAttributes  = $this->buyRequest->getSuperAttribute();
            $childProduct     = $this->configurableEntity->getProductByAttributes(
                $superAttributes,
                $this->originalProduct
            );
            $this->skuArray[] = $childProduct->getSku();
        } else {
            $this->skuArray[] = $this->originalProduct->getSku();
        }

        return;
    }

    /**
     * Recollect totals and save quote and items after changes
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return void
     */
    protected function processQuote($quote)
    {
        if (!$quote) {
            return;
        }

        if ($quote->getTotalsCollectedFlag() == true) {
            $quote->setTotalsCollectedFlag(false)->collectTotals();
        }
        $quote->setCanChangeQuoteItemsOrder(true);
        $quote->save();
    }

    /**
     * Add custom SKU to buyRequest
     *
     * @return void
     */
    protected function addCustomSkuToBuyRequest()
    {
        $infoBuyRequest = $this->quoteItem->getOptionByCode('info_buyRequest');
        if (empty($infoBuyRequest)) {
            return;
        }

        $this->buyRequest->setData('sku_policy_sku', implode('-', $this->skuArray));
        $infoBuyRequest->setValue($this->baseHelper->encodeBuyRequestValue($this->buyRequest->getData()));
        $this->quoteItem->addOption($infoBuyRequest);
    }

    /**
     * Save new/modified quote item's options for correct recollecting totals
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return void
     */
    protected function saveNewQuoteItemOptions($quote)
    {
        $quote->save();
    }

    /**
     * Check if there is only group SKU Policy on options
     *
     * @param array $options
     * @return void
     */
    protected function checkSkuPolicyGroupOnly($options)
    {
        foreach ($options as $optionId => $values) {
            $option = $this->quoteProduct->getOptionById($optionId);
            if (!$option) {
                continue;
            }
            $option->setProduct($this->quoteProduct);
            $skuPolicy = $option->getSkuPolicy() == Helper::SKU_POLICY_USE_CONFIG
                ? $this->productSkuPolicy
                : $option->getSkuPolicy();
            if ($skuPolicy != Helper::SKU_POLICY_GROUPED) {
                $this->isGroupedSkuPolicyOnly = false;

                return;
            }
        }
        $this->isGroupedSkuPolicyOnly = true;
    }

    /**
     * Process buy request options to apply SKU policy
     *
     * @param array $options
     * @return void
     */
    protected function processBuyRequestOptions($options)
    {
        $this->quoteItem->getQuote()->setIsProcessingOptions(true);
        foreach ($options as $optionId => $values) {
            $option = $this->quoteProduct->getOptionById($optionId);
            if (!$option) {
                continue;
            }
            $option->setProduct($this->quoteProduct);

            if (in_array($option->getType(), $this->baseHelper->getSelectableOptionTypes())) {
                $this->processSelectableOption($option, $optionId, $values);
            } else {
                $this->processNonSelectableOption($option, $optionId, $values);
            }
        }
        $this->quoteItem->getQuote()->setIsProcessingOptions(false);
    }

    /**
     * Process selectable option
     *
     * @param ProductOptionInterface $option
     * @param int $optionId
     * @param array $values
     * @return void
     */
    protected function processSelectableOption($option, $optionId, $values)
    {
        if (is_array($values)) {
            $optionTypeIds = $values;
        } else {
            $optionTypeIds = explode(',', $values);
        }
        $isOneTime = $option->getOneTime();
        $skuPolicy = $option->getSkuPolicy() == Helper::SKU_POLICY_USE_CONFIG
            ? $this->productSkuPolicy
            : $option->getSkuPolicy();

        $replacementSkus = [];
        foreach ($optionTypeIds as $index => $optionTypeId) {
            if (!$optionTypeId) {
                continue;
            }
            $value = $option->getValueById($optionTypeId);
            $sku   = $value->getSku();
            if (!$sku) {
                continue;
            }
            $replacementSkus[] = $sku;

            if ($skuPolicy == Helper::SKU_POLICY_STANDARD) {
                $this->skuArray[] = $sku;
            } elseif ($skuPolicy == Helper::SKU_POLICY_REPLACEMENT) {
                $this->skuArray[0]          = implode('-', $replacementSkus);
                $this->isAllOptionsPolicyStandard = false;
            } elseif ($skuPolicy == Helper::SKU_POLICY_GROUPED
                || $skuPolicy == Helper::SKU_POLICY_INDEPENDENT
            ) {
                $this->isAllOptionsPolicyStandard = false;
                try {
                    $excludedItemCandidate = $this->productRepository->get($sku);
                } catch (NoSuchEntityException $e) {
                    $this->skuArray[] = $sku;
                    continue;
                }
                if (!$this->isExcludedItemValid($excludedItemCandidate)) {
                    $this->skuArray[] = $sku;
                    continue;
                }

                $optionQty      = $this->getOptionQty($this->buyRequest, $optionId, $optionTypeId);
                $optionTotalQty = $isOneTime ? $optionQty : $optionQty * $this->quoteItem->getQty();

                $request = $this->dataObjectFactory->create();
                $request->setQty($optionTotalQty);

                $excludedProduct = $this->productRepository->get($sku, false, $this->quoteItem->getStoreId(), true);
                if ($this->helper->isSplitIndependents()) {
                    $excludedProduct->addCustomOption('parent_custom_option_id', $option->getOptionId());
                }

                $excludedItem = $this->quoteItem->getQuote()->addProduct(
                    $excludedProduct,
                    $request
                );
                if (!is_object($excludedItem)) {
                    continue;
                }

                $this->quoteItem->getQuote()->setIsSuperMode(true);
                $price = $this->modelPrice->getPrice($option, $value);
                $price = $this->priceCurrency->convert(
                    $price,
                    $this->quoteItem->getQuote()->getStore()
                );
                $excludedItem->setOriginalCustomPrice($price);
                $excludedItem->setCustomPrice($price);

                if ($this->helperFeatures->isWeightEnabled()) {
                    $excludedItem->setWeight($value->getWeight());
                }
                if ($this->helperFeatures->isCostEnabled()) {
                    $excludedItem->setCost($value->getCost());
                }
                $excludedItem->setIsSkuPolicyApplied(true);
                if (!in_array($excludedItem, $this->newQuoteItems, true)) {
                    $this->newQuoteItems[] = $excludedItem;
                }

                $this->removeOptionAndOptionValueFromItem(
                    $values,
                    $optionId,
                    $index
                );

                if (!$this->toCart) {
                    $this->removeOutdatedQuoteItemData();
                }

                $this->isItemChanged = true;
                if ($skuPolicy == Helper::SKU_POLICY_GROUPED && $this->isGroupedSkuPolicyOnly) {
                    $this->isItemRemoved = true;
                }
            }
        }

        $this->eventManager->dispatch(
            'mageworx_apo_add_independedt_quote_items',
            [
                'new_items' => $this->newQuoteItems
            ]
        );
    }

    /**
     * Process non-selectable option
     *
     * @param ProductOptionInterface $option
     * @param int $optionId
     * @param array $values
     * @return bool
     */
    protected function processNonSelectableOption($option, $optionId, $values)
    {
        $sku = $option->getSku();
        if (!$this->isNonSelectableValuesValid($values, $sku)) {
            return false;
        }

        $isOneTime = $option->getOneTime();
        $skuPolicy = $option->getSkuPolicy() == Helper::SKU_POLICY_USE_CONFIG
            ? $this->productSkuPolicy
            : $option->getSkuPolicy();

        if ($skuPolicy == Helper::SKU_POLICY_STANDARD) {
            $this->skuArray[] = $sku;
        } elseif ($skuPolicy == Helper::SKU_POLICY_REPLACEMENT) {
            $this->skuArray[0]          = $sku;
            $this->isAllOptionsPolicyStandard = false;
        } elseif ($skuPolicy == Helper::SKU_POLICY_GROUPED
            || $skuPolicy == Helper::SKU_POLICY_INDEPENDENT
        ) {
            $this->isAllOptionsPolicyStandard = false;
            try {
                $excludedItemCandidate = $this->productRepository->get($sku);
            } catch (NoSuchEntityException $e) {
                $this->skuArray[] = $sku;

                return false;
            }
            if (!$this->isExcludedItemValid($excludedItemCandidate)) {
                $this->skuArray[] = $sku;

                return false;
            }

            $optionTotalQty = $isOneTime ? 1 : $this->quoteItem->getQty();
            $request        = $this->dataObjectFactory->create();
            $request->setQty($optionTotalQty);

            $excludedProduct = $this->productRepository->get($sku, false, $this->quoteItem->getStoreId(), true);
            if ($this->helper->isSplitIndependents()) {
                $excludedProduct->addCustomOption('parent_custom_option_id', $option->getOptionId());
            }
            $excludedItem = $this->quoteItem->getQuote()->addProduct(
                $excludedProduct,
                $request
            );
            if (!is_object($excludedItem)) {
                return false;
            }

            $this->quoteItem->getQuote()->setIsSuperMode(true);
            $price = $this->priceCurrency->convert(
                $option->getPrice(),
                $this->quoteItem->getQuote()->getStore()
            );
            $excludedItem->setCustomPrice($price);
            $excludedItem->setOriginalCustomPrice($price);

            $excludedItem->setIsSkuPolicyApplied(true);
            if (!in_array($excludedItem, $this->newQuoteItems, true)) {
                $this->newQuoteItems[] = $excludedItem;
            }

            $this->removeOptionFromItem($optionId);
            if (!$this->toCart) {
                $this->removeOutdatedQuoteItemData();
            }

            $this->isItemChanged = true;
            if ($skuPolicy == Helper::SKU_POLICY_GROUPED && $this->isGroupedSkuPolicyOnly) {
                $this->isItemRemoved = true;
            }
        }

        $this->eventManager->dispatch(
            'mageworx_apo_add_independedt_quote_items',
            [
                'new_items' => $this->newQuoteItems
            ]
        );
    }

    /**
     * Validate quote item excluded by independent/grouped mode
     *
     * @param Item $quoteItem
     * @return bool
     */
    protected function isExcludedItemValid($quoteItem)
    {
        if (!in_array($quoteItem->getTypeId(), ['simple', 'virtual', 'downloadable'])) {
            return false;
        }

        if ($quoteItem->getRequiredOptions()) {
            return false;
        }

        return true;
    }

    /**
     * Validate non-selectable values
     *
     * @param array $values
     * @param string $sku
     * @return bool
     */
    protected function isNonSelectableValuesValid($values, $sku)
    {
        if (!$values || !$sku) {
            return false;
        }
        $isValuesEmpty = true;
        if (is_array($values)) {
            foreach ($values as $value) {
                if ($value) {
                    $isValuesEmpty = false;
                }
            }
        } else {
            $isValuesEmpty = false;
        }

        return !$isValuesEmpty;
    }

    /**
     * Modify quote item:
     * Increase qty if it is changed
     * Delete from quote items collection if it is removed
     *
     * @return void
     */
    protected function modifyQuoteItem()
    {
        if ($this->isItemRemoved) {
            $itemsCollection = $this->quoteItem->getQuote()->getItemsCollection();
            foreach ($itemsCollection as $key => $collectionItem) {
                if ($collectionItem === $this->quoteItem) {
                    $this->removeQuoteItem($itemsCollection, $key);
                }
            }
        } elseif ($this->isItemChanged) {
            $itemsCollection     = $this->quoteItem->getQuote()->getItemsCollection();
            $this->isItemRemoved = false;
            $isItemIncrease      = false;
            foreach ($itemsCollection as $key => $collectionItem) {
                if ($collectionItem->getProductId() == $this->quoteItem->getProductId()
                    && $collectionItem->getSku() == $this->quoteItem->getSku()
                    && $collectionItem->getProductType() == $this->quoteItem->getProductType()
                    && $collectionItem !== $this->quoteItem
                ) {
                    $currentOptions = !empty($this->buyRequest['options']) ? $this->buyRequest['options'] : false;

                    $collectionProduct = $collectionItem->getProduct();
                    $collectionOptions = false;
                    if ($collectionProduct->getHasOptions()) {
                        $buyRequest = $this->baseHelper->getInfoBuyRequest($collectionProduct);
                        if (!empty($buyRequest['options'])) {
                            $collectionOptions = $buyRequest['options'];
                        }
                    }

                    // compare options
                    if ($collectionOptions === $currentOptions) {
                        if (!$this->isUpdateCartItemAction()) {
                            $collectionItem->setQty($collectionItem->getQty() + $this->quoteItem->getQty());
                            $isItemIncrease = true;
                        } else {
                            $collectionItem->setQty($this->quoteItem->getQty());
                        }
                        $this->isItemRemoved = true;
                    }
                }
                if ($this->isItemRemoved && $collectionItem === $this->quoteItem && !$this->isUpdateCartItemAction()) {
                    $this->removeQuoteItem($itemsCollection, $key);
                }
            }
            foreach ($itemsCollection as $key => $collectionItem) {
                if ($collectionItem === $this->quoteItem && $isItemIncrease) {
                    $this->removeQuoteItem($itemsCollection, $key);
                }
            }
        }
    }

    protected function removeQuoteItem($itemsCollection, $key)
    {
        $this->quoteItem->isDeleted(true);
        $this->quoteItem->save();
        $itemsCollection->removeItemByKey($key);
    }

    /**
     * Check if it is update cart item action
     *
     * @return bool
     */
    protected function isUpdateCartItemAction()
    {
        if ($this->request->getModuleName() == 'checkout'
            && $this->request->getControllerName() == 'cart'
            && $this->request->getActionName() == 'updateItemOptions'
        ) {
            return true;
        }

        return false;
    }

    /**
     * Clean quote item data to recollect totals
     * Used in applySkuPolicyToOrder only case
     *
     * @return void
     */
    protected function removeOutdatedQuoteItemData()
    {
        $requiredKeys = [
            'store_id',
            'item_id',
            'quote_id',
            'product_id',
            'product_type',
            'sku',
            'name',
            'qty',
            'custom_price',
            'original_custom_price'
        ];
        foreach ($this->quoteItem->getData() as $key => $value) {
            if (in_array($key, $requiredKeys)) {
                continue;
            }
            $this->quoteItem->unsetData($key);
        }

        return;
    }

    /**
     * Get option's qty
     *
     * @param array $post
     * @param int $optionId
     * @param int $optionTypeId
     * @return int
     */
    protected function getOptionQty($post, $optionId, $optionTypeId)
    {
        if (isset($post['options_qty'][$optionId][$optionTypeId])) {
            $optionQty = intval($post['options_qty'][$optionId][$optionTypeId]);
        } elseif (isset($post['options_qty'][$optionId])) {
            $optionQty = intval($post['options_qty'][$optionId]);
        } else {
            $optionQty = 1;
        }

        return $optionQty;
    }

    /**
     * Remove option and option value from quote item
     *
     * @param array|null $values
     * @param int $optionId
     * @param string $index
     * @return void
     */
    protected function removeOptionAndOptionValueFromItem(&$values, $optionId, $index)
    {
        //OrderEditor send values as string
        if (!is_array($values) && $this->systemHelper->isEditingByOrderEditor()) {
            $values = explode(',', $values);
        }
        if (is_array($values)) {
            unset($values[$index]);
        } else {
            $values = '';
        }
        if ($values) {
            $options            = $this->buyRequest->getData('options');
            $options[$optionId] = $values;
            $this->buyRequest->setData('options', $options);
            $itemOption = $this->quoteItem->getOptionByCode('option_' . $optionId);
            $itemOption->setValue(is_array($values) ? implode(',', $values) : $values);
            $this->quoteItem->addOption($itemOption);
        } else {
            $this->removeOptionFromItem($optionId);
        }
    }

    /**
     * Remove option from quote item
     *
     * @param int $optionId
     * @return void
     */
    protected function removeOptionFromItem($optionId)
    {
        $options = $this->buyRequest->getData('options');
        unset($options[$optionId]);
        $this->buyRequest->setData('options', $options);
        $this->quoteItem->removeOption('option_' . $optionId);

        $itemOptionIds = $this->quoteItem->getOptionByCode('option_ids');
        $optionIds     = $itemOptionIds->getValue();
        if ($optionIds) {
            $optionIds = explode(',', $optionIds);
            $i         = array_search($optionId, $optionIds);
            if ($i !== false) {
                unset($optionIds[$i]);
            }
            if ($optionIds) {
                $optionIds = implode(',', $optionIds);
            }
        }
        if ($optionIds) {
            $itemOptionIds->setValue($optionIds);
            $this->quoteItem->addOption($itemOptionIds);
        } else {
            $this->quoteItem->removeOption('option_ids');
        }
    }

    /**
     * Check conditions to start applying SKU policy
     *
     * @param array $items
     * @param \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment
     * @return bool
     */
    protected function out($items, $shippingAssignment)
    {
        if (!$this->helper->isEnabledSkuPolicy()) {
            return true;
        }

        if (!$items) {
            return true;
        }

        if ($shippingAssignment->getShipping() &&
            $shippingAssignment->getShipping()->getAddress() &&
            $shippingAssignment->getShipping()->getAddress()->getAddressType() == 'billing') {
            return true;
        }

        return false;
    }

    /**
     * Check if original and quote product has options
     *
     * @param ProductInterface $originalProduct
     * @param ProductInterface $quoteProduct
     * @return bool
     */
    protected function hasOptions($originalProduct, $quoteProduct)
    {
        if (!$originalProduct || !$quoteProduct) {
            return false;
        }

        return ($originalProduct->getHasOptions() && $quoteProduct->getHasOptions())
            || ($originalProduct->getOptions() && $quoteProduct->getOptions());
    }

    /**
     * Set "is submit quote" flag
     *
     * @used to avoid additional validation for bundle products
     *
     * @param bool $status
     * @return void
     */
    public function setIsSubmitQuoteFlag($status)
    {
        $this->isSubmitQuoteFlag = (bool)$status;
    }

    /**
     * Get "is submit quote" flag
     *
     * @used to avoid additional validation for bundle products
     *
     * @return bool
     */
    public function getIsSubmitQuoteFlag()
    {
        return $this->isSubmitQuoteFlag;
    }
}
