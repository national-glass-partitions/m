<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionInventory\Observer;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use MageWorx\OptionInventory\Helper\Data as HelperData;
use MageWorx\OptionInventory\Model\StockProvider;
use MageWorx\OptionInventory\Model\Validator;

/**
 * Class ValidateOptionsInventory.
 * This observer validate requested option values inventory with original
 *
 * @package MageWorx\OptionInventory\Observer
 */
class ValidateOptionsInventory implements ObserverInterface
{
    protected Validator $validator;
    protected StockProvider $stockProvider;
    protected RequestInterface $request;
    protected HelperData $helperData;

    /**
     * ValidateOptionsInventory constructor.
     *
     * @param Validator $validator
     * @param StockProvider $stockProvider
     * @param RequestInterface $request
     */
    public function __construct(
        Validator $validator,
        StockProvider $stockProvider,
        RequestInterface $request,
        HelperData $helperData
    ) {
        $this->validator     = $validator;
        $this->stockProvider = $stockProvider;
        $this->request       = $request;
        $this->helperData    = $helperData;
    }

    public function execute(EventObserver $observer)
    {
        if ($this->helperData->isEnabledOptionInventory()) {
            $item = $observer->getEvent()->getItem();
            if (!$item ||
                !$item->getProductId() ||
                !$item->getQuote() ||
                $item->getQuote()->getIsSuperMode()
            ) {
                return;
            }

            $quote = $item->getQuote();
            $cart  = $this->request->getParam('cart', []);

            $allQuoteItems     = $quote->getAllItems();
            $requestedValues   = $this->stockProvider->getRequestedData($allQuoteItems, $cart);
            $originQuoteValues = $this->stockProvider->getOriginData($requestedValues);

            try {
                $this->validator->validate($requestedValues, $originQuoteValues);
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $item->addErrorInfo(
                    'optioninventory',
                    \Magento\CatalogInventory\Helper\Data::ERROR_QTY,
                    $e->getMessage()
                );

                $quote->addErrorInfo(
                    'error',
                    'optioninventory',
                    \Magento\CatalogInventory\Helper\Data::ERROR_QTY,
                    $e->getMessage()
                );
            }
        }
    }
}
