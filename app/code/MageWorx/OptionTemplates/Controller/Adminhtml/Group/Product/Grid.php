<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionTemplates\Controller\Adminhtml\Group\Product;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\View\LayoutFactory;
use Magento\Framework\Controller\Result\Raw;
use Magento\Catalog\Controller\Adminhtml\Product\Builder as ProductBuilder;
use MageWorx\OptionTemplates\Block\Adminhtml\Group\Tab\Products as ProductsTab;

class Grid extends \Magento\Catalog\Controller\Adminhtml\Product
{
    /**
     * @var RawFactory
     */
    protected $resultRawFactory;

    /**
     * @var LayoutFactory
     */
    protected $layoutFactory;

    /**
     * @var ProductBuilder
     */
    protected $productBuilder;

    /**
     * @param Context $context
     * @param RawFactory $resultRawFactory
     * @param LayoutFactory $layoutFactory
     * @param ProductBuilder $productBuilder
     */
    public function __construct(
        Context $context,
        RawFactory $resultRawFactory,
        LayoutFactory $layoutFactory,
        ProductBuilder $productBuilder
    ) {
        parent::__construct($context, $productBuilder);
        $this->resultRawFactory = $resultRawFactory;
        $this->layoutFactory    = $layoutFactory;
        $this->productBuilder   = $productBuilder;
    }

    /**
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var Raw $resultRaw */
        $resultRaw = $this->resultRawFactory->create();

        return $resultRaw->setContents(
            $this->layoutFactory->create()->createBlock(
                ProductsTab::class,
                'group.products.grid'
            )->toHtml()
        );
    }
}
