<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionSkuPolicy\Plugin;

use Magento\Directory\Controller\Currency\SwitchAction;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Checkout\Model\Cart;
use Magento\Directory\Model\CurrencyFactory;
use Psr\Log\LoggerInterface as Logger;

class UpdateQuoteItemsOnCurrencyChange
{
    protected StoreManagerInterface $storeManager;
    protected RedirectInterface $redirect;
    protected RequestInterface $request;
    protected ResponseInterface $response;
    protected Cart $cart;
    protected CurrencyFactory $currencyFactory;
    protected Logger $logger;

    /**
     * @param RedirectInterface $redirect
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param Cart $cart
     * @param CurrencyFactory $currencyFactory
     * @param StoreManagerInterface $storeManager
     * @param Logger $logger
     */
    public function __construct(
        RedirectInterface $redirect,
        RequestInterface $request,
        ResponseInterface $response,
        CurrencyFactory $currencyFactory,
        Cart $cart,
        Logger $logger,
        StoreManagerInterface $storeManager
    ) {
        $this->redirect        = $redirect;
        $this->request         = $request;
        $this->response        = $response;
        $this->cart            = $cart;
        $this->currencyFactory = $currencyFactory;
        $this->storeManager    = $storeManager;
        $this->logger          = $logger;
    }

    /**
     * @param SwitchAction $subject
     * @param \Closure $proceed
     *
     * @return void
     */
    public function aroundExecute(
        SwitchAction $subject,
        \Closure $proceed
    ) {
        $currency = (string)$this->request->getParam('currency');
        if ($currency) {
            try {
                $quote = $this->cart->getQuote();
                $quote->setIsSuperMode(true);

                $quoteItemsCollection = $quote->getItemsCollection();
                foreach ($quoteItemsCollection as $quoteItem) {
                    $customPrice = $quoteItem->getCustomPrice();
                    if (!$customPrice) {
                        continue;
                    }

                    $price = $this->convertPrice($customPrice, $quote->getQuoteCurrencyCode(), $currency);
                    $quoteItem->setOriginalCustomPrice($price);
                    $quoteItem->setCustomPrice($price);
                    $quoteItem->save();
                }

                $quote->setTotalsCollectedFlag(false)->collectTotals();
                $quote->save();
            } catch (\Exception $e) {
                $this->logger->critical($e->getMessage());
            }
            $this->storeManager->getStore()->setCurrentCurrencyCode($currency);
        }
        $storeUrl = $this->storeManager->getStore()->getBaseUrl();
        $this->response->setRedirect($this->redirect->getRedirectUrl($storeUrl));
    }

    /**
     * Convert price from one currency to another
     *
     * @param float $price
     * @param string $currencyFrom
     * @param string $currencyTo
     *
     * @return float
     */
    protected function convertPrice($price, $currencyFrom, $currencyTo)
    {
        $baseCurrency = $this->storeManager->getStore()->getBaseCurrency()->getCode();

        if ($currencyFrom !== $currencyTo) {
            if ($baseCurrency === $currencyFrom) {
                $currencyModel = $this->currencyFactory->create()->load($currencyFrom);
                $rate          = $currencyModel->getRate($currencyTo);
                $price         = $price * $rate;
            } elseif ($baseCurrency === $currencyTo) {
                $currencyModel = $this->currencyFactory->create()->load($currencyTo);
                $rate          = $currencyModel->getRate($currencyFrom);
                $price         = $price / $rate;
            } else {
                $currencyModel = $this->currencyFactory->create()->load($currencyFrom);
                $rate          = $currencyModel->getAnyRate($currencyTo);
                $price         = $price * $rate;
            }
        }

        return $price;
    }
}
