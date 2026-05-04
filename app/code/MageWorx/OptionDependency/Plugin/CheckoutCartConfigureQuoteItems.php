<?php

/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionDependency\Plugin;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Model\Quote\Item\Option as QuoteItemOption;
use Magento\Backend\Model\Session\Quote as SessionQuote;
use Magento\Catalog\Helper\Product\Composite as ProductCompositeHelper;
use Magento\Catalog\Helper\Product\View as ProductViewHelper;
use Psr\Log\LoggerInterface;
use Magento\Checkout\Controller\Cart\Configure;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionDependency\Model\HiddenDependents;

class CheckoutCartConfigureQuoteItems extends Configure
{
    protected BaseHelper $baseHelper;
    protected RequestInterface $request;
    protected QuoteItem $quoteItem;
    protected QuoteItemOption $quoteItemOption;
    protected SessionQuote $sessionQuote;
    protected ProductCompositeHelper $productCompositeHelper;
    protected HiddenDependents $hiddenDependents;
    protected LoggerInterface $logger;
    protected ProductViewHelper $productViewHelper;

    public function __construct(
        BaseHelper $baseHelper,
        RequestInterface $request,
        QuoteItem $quoteItem,
        QuoteItemOption $quoteItemOption,
        SessionQuote $sessionQuote,
        ProductCompositeHelper $productCompositeHelper,
        HiddenDependents $hiddenDependents,
        LoggerInterface $logger,
        ProductViewHelper $productViewHelper,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        CustomerCart $cart
    ) {
        $this->baseHelper             = $baseHelper;
        $this->request                = $request;
        $this->quoteItem              = $quoteItem;
        $this->quoteItemOption        = $quoteItemOption;
        $this->sessionQuote           = $sessionQuote;
        $this->productCompositeHelper = $productCompositeHelper;
        $this->hiddenDependents       = $hiddenDependents;
        $this->logger                 = $logger;
        $this->productViewHelper      = $productViewHelper;
        parent::__construct(
            $context,
            $scopeConfig,
            $checkoutSession,
            $storeManager,
            $formKeyValidator,
            $cart
        );
    }

    /**
     * Process dependency copying
     *
     * @param Configure $subject
     * @param \Closure $proceed
     * @return \Magento\Framework\View\Result\Page|\Magento\Framework\Controller\Result\Redirect
     */
    public function aroundExecute($subject, \Closure $proceed)
    {
        $id        = (int)$this->getRequest()->getParam('id');
        $productId = (int)$this->getRequest()->getParam('product_id');
        $quoteItem = null;
        if ($id) {
            $quoteItem = $this->cart->getQuote()->getItemById($id);
        }

        try {
            if (!$quoteItem || $productId != $quoteItem->getProduct()->getId()) {
                $this->messageManager->addErrorMessage(
                    __("The quote item isn't found. Verify the item and try again.")
                );
                return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('checkout/cart');
            }

            $params = new \Magento\Framework\DataObject();
            $params->setCategoryId(false);
            $params->setConfigureMode(true);
            $params->setBuyRequest($quoteItem->getBuyRequest());

            $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
            $this->productViewHelper->prepareAndRender(
                $resultPage,
                $quoteItem->getProduct()->getId(),
                $this,
                $params
            );

            $this->hiddenDependents->calculateConfigureQuoteItemsHiddenDependents(
                $quoteItem->getProduct(),
                $quoteItem->getBuyRequest()
            );

            return $resultPage;
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('We cannot configure the product.'));
            $this->logger->critical($e);
            return $this->_goBack();
        }
    }
}
