<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionInventory\Controller\Adminhtml\Report;

/**
 * Class Index. Set active menu, title, add breadcrumb
 * @package MageWorx\OptionInventory\Controller\Adminhtml\Report
 */
class Index extends \Magento\Backend\App\Action
{
    protected \Magento\Framework\View\Result\PageFactory $resultPageFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Report list.
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('MageWorx_OptionInventory::optioninventory_report');
        $resultPage->getConfig()->getTitle()->prepend(__('Option Inventory Report'));
        $resultPage->addBreadcrumb(__('Option Inventory'), __('Option Inventory'));
        $resultPage->addBreadcrumb(__('Option Inventory Report'), __('Option Inventory Report'));
        return $resultPage;
    }
}
