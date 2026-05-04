<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionImportExport\Controller\Adminhtml\ImportExport;

use Magento\Framework\Controller\ResultFactory;

class Index extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'MageWorx_OptionImportExport::import_export';
    
    /**
     * Import and export Page
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $this->messageManager->addNoticeMessage(
            $this->_objectManager->get(\Magento\ImportExport\Helper\Data::class)->getMaxUploadSizeMessage()
        );

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        $resultPage->setActiveMenu('MageWorx_OptionImportExport::system_importexport_templates');
        $resultPage->addContent(
            $resultPage->getLayout()->createBlock(
                \MageWorx\OptionImportExport\Block\Adminhtml\ImportExport::class
            )
        );
        $resultPage->getConfig()->getTitle()->prepend(__('MageWorx Options Import'));
        return $resultPage;
    }
}
