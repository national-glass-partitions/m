<?php
/**
 * BSS Commerce Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://bsscommerce.com/Bss-Commerce-License.txt
 *
 * @category   BSS
 * @package    Bss_DupCmsPageBlock
 * @author     Extension Team
 * @copyright  Copyright (c) 2017-2018 BSS Commerce Co. ( http://bsscommerce.com )
 * @license    http://bsscommerce.com/Bss-Commerce-License.txt
 */
namespace Bss\DupCmsPageBlock\Controller\Adminhtml\Page;

class Duplicate extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Cms\Model\PageFactory
     */
    protected $pageModel;
    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @var \Bss\DupCmsPageBlock\Helper\Data
     */
    protected $helperBss;

    /**
     * Duplicate constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Bss\DupCmsPageBlock\Helper\Data $helperBss
     * @param \Magento\Cms\Model\PageFactory $pageModel
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Bss\DupCmsPageBlock\Helper\Data $helperBss,
        \Magento\Cms\Model\PageFactory $pageModel,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        $this->pageModel = $pageModel;
        $this->helperBss = $helperBss;
        $this->coreRegistry = $coreRegistry;
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * Duplicate CMS page
     *
     * @return \Magento\Framework\Controller\ResultInterface
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('page_id');
        $model = $this->pageModel->create();
        $duplicatePage = $this->pageModel->create();

        $cmsPageData = $this->helperBss->duplicate($id, $model, 'page');
        if (!$cmsPageData) {
            $this->messageManager->addErrorMessage(__('This page no longer exists.'));
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('*/*/');
        }
        try {
            $duplicatePage->setData($cmsPageData)->save();
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__("This page can not be duplicated"));
        }
        $this->messageManager->addSuccessMessage(__('The page is duplicated'));
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('cms/page/edit', ['page_id' => $duplicatePage->getId()]);
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Bss_DupCmsPageBlock::duplicate_cmspage');
    }
}
