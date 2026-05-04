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
namespace Bss\DupCmsPageBlock\Controller\Adminhtml\Block;

class Duplicate extends \Magento\Cms\Controller\Adminhtml\Block
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Cms\Model\BlockFactory
     */
    protected $blockModel;

    /**
     * @var \Bss\DupCmsPageBlock\Helper\Data
     */
    protected $helperBss;

    /**
     * Duplicate constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Bss\DupCmsPageBlock\Helper\Data $helperBss
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Cms\Model\BlockFactory $blockModel
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Bss\DupCmsPageBlock\Helper\Data $helperBss,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Cms\Model\BlockFactory $blockModel,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        $this->helperBss = $helperBss;
        $this->blockModel = $blockModel;
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context, $coreRegistry);
    }

    /**
     * Duplicate CMS block
     *
     * @return \Magento\Framework\Controller\ResultInterface
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('block_id');
        $model = $this->blockModel->create();
        $duplicateBlock = $this->blockModel->create();

        $cmsBlockData = $this->helperBss->duplicate($id, $model, 'block');
        if (!$cmsBlockData) {
            $this->messageManager->addErrorMessage(__('This block no longer exists.'));
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('*/*/');
        }
        try {
            $duplicateBlock->setData($cmsBlockData)->save();
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__("This block can not be duplicated"));
        }
        $this->messageManager->addSuccessMessage(__('The block is duplicated'));
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('cms/block/edit', ['block_id' => $duplicateBlock->getId()]);
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Bss_DupCmsPageBlock::duplicate_cmsblock');
    }
}
