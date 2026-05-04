<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionInventory\Controller\StockMessage;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Serialize\Serializer\Json as Serializer;
use MageWorx\OptionInventory\Helper\Data;
use MageWorx\OptionInventory\Model\StockProvider;

/**
 * Class Update.
 * This controller updates options stock message on the product page
 */
class Update extends Action
{
    protected ?StockProvider $stockProvider = null;
    protected Serializer $serializer;
    protected Data $helperData;

    public function __construct(
        Context $context,
        StockProvider $stockProvider,
        Serializer $serializer,
        Data $helperData
    ) {
        parent::__construct($context);
        $this->stockProvider = $stockProvider;
        $this->serializer    = $serializer;
        $this->helperData    = $helperData;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        if (!$this->helperData->isEnabledOptionInventory()) {
            return;
        }
        $this->getRequest()->getParams();
        $optionConfig    = $this->getRequest()->getPost('opConfig');

        if (!$optionConfig) {
            return;
        }
        $options = $this->serializer->unserialize($optionConfig);
        if (isset($options['bundleId'])) {
            return;
        }

        $options = $this->stockProvider->updateOptionsStockMessage($options);

        return $this->getResponse()->setBody($this->serializer->serialize(['result'=> $options]));
    }
}
