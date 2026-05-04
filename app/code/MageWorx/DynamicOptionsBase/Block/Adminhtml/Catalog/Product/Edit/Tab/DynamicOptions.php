<?php
/**
 * Copyright © MageWorx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MageWorx\DynamicOptionsBase\Block\Adminhtml\Catalog\Product\Edit\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Model\Locator\LocatorInterface;

class DynamicOptions extends \Magento\Framework\View\Element\Template
{
    /**
     * @var string
     */
    protected $_template = 'product/edit/dynamic_options.phtml';
    protected LocatorInterface $locator;

    /**
     * DynamicOptions constructor.
     *
     * @param Context $context
     * @param LocatorInterface $locator
     * @param array $data
     */
    public function __construct(
        Context $context,
        LocatorInterface $locator,
        array $data = []
    ) {
        $this->locator = $locator;
        parent::__construct($context, $data);
    }

    /**
     * @return \Magento\Catalog\Api\Data\ProductInterface
     */
    public function getProduct()
    {
        return $this->locator->getProduct();
    }

    /**
     * @return string
     */
    public function getCurrencySymbol()
    {
        return (string)$this->locator->getStore()->getBaseCurrency()->getCurrencySymbol();
    }
}
