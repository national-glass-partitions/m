<?php

/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionDependency\Plugin\Adminhtml;

use Magento\Framework\DataObject;
use Magento\Framework\App\RequestInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Model\Quote\Item\Option as QuoteItemOption;
use Magento\Backend\Model\Session\Quote as SessionQuote;
use Magento\Catalog\Helper\Product\Composite as ProductCompositeHelper;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionDependency\Model\HiddenDependents;

class OrderCreateConfigureQuoteItems
{
    protected BaseHelper $baseHelper;
    protected RequestInterface $request;
    protected ProductRepositoryInterface $productRepository;
    protected QuoteItem $quoteItem;
    protected QuoteItemOption $quoteItemOption;
    protected SessionQuote $sessionQuote;
    protected ProductCompositeHelper $productCompositeHelper;
    protected HiddenDependents $hiddenDependents;

    public function __construct(
        BaseHelper $baseHelper,
        RequestInterface $request,
        ProductRepositoryInterface $productRepository,
        QuoteItem $quoteItem,
        QuoteItemOption $quoteItemOption,
        SessionQuote $sessionQuote,
        ProductCompositeHelper $productCompositeHelper,
        HiddenDependents $hiddenDependents
    ) {
        $this->baseHelper             = $baseHelper;
        $this->request                = $request;
        $this->productRepository      = $productRepository;
        $this->quoteItem              = $quoteItem;
        $this->quoteItemOption        = $quoteItemOption;
        $this->sessionQuote           = $sessionQuote;
        $this->productCompositeHelper = $productCompositeHelper;
        $this->hiddenDependents       = $hiddenDependents;
    }

    /**
     * Process dependency copying
     *
     * @param \Magento\Sales\Controller\Adminhtml\Order\Create\ConfigureQuoteItems $subject
     * @param \Closure $proceed
     * @return \Magento\Framework\View\Result\Layout
     */
    public function aroundExecute($subject, \Closure $proceed)
    {
        $configureResult = new DataObject();
        try {
            $quoteItemId = (int)$this->request->getParam('id');
            if (!$quoteItemId) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('The quote item ID needs to be received. Set the ID and try again.')
                );
            }

            $quoteItem = $this->quoteItem->load($quoteItemId);
            if (!$quoteItem->getId()) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('The quote item needs to be loaded. Load the item and try again.')
                );
            }

            $configureResult->setOk(true);
            $optionCollection = $this->quoteItemOption->getCollection()
                                                      ->addItemFilter([$quoteItemId]);
            $options          = $optionCollection->getOptionsByItem($quoteItem);
            $quoteItem->setOptions($options);

            $configureResult->setBuyRequest($quoteItem->getBuyRequest());
            $configureResult->setCurrentStoreId($quoteItem->getStoreId());
            $configureResult->setProductId($quoteItem->getProductId());
            $configureResult->setCurrentCustomerId($this->sessionQuote->getCustomerId());

            $product = $this->productRepository->getById(
                $quoteItem->getProductId(),
                false,
                $quoteItem->getStoreId()
            );
            $this->hiddenDependents->calculateConfigureQuoteItemsHiddenDependents(
                $product,
                $quoteItem->getBuyRequest()
            );
        } catch (\Exception $e) {
            $configureResult->setError(true);
            $configureResult->setMessage($e->getMessage());
        }

        return $this->productCompositeHelper->renderConfigureResult($configureResult);
    }
}
