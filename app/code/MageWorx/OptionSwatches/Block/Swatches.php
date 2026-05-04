<?php
/**
 * Copyright © 2017 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionSwatches\Block;

use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use MageWorx\OptionSwatches\Helper\Data as Helper;

class Swatches extends Template
{
    protected EncoderInterface $jsonEncoder;
    protected Helper $helper;

    public function __construct(
        Context $context,
        EncoderInterface $jsonEncoder,
        Helper $helper,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $data
        );
        $this->jsonEncoder = $jsonEncoder;
        $this->helper = $helper;
    }

    /**
     * @return string
     */
    public function getJsonData()
    {
        $data = [
            'isEnabledRedirectToCart' => $this->helper->isEnabledRedirectToCart($this->_storeManager->getStore())
        ];
        return $this->jsonEncoder->encode($data);
    }
}
