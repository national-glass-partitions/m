<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionImportExport\Plugin;

use MageWorx\OptionBase\Helper\Data as BaseHelper;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\ImportExport\Model\Export as ExportModel;
use Magento\Backend\App\Action;
use Psr\Log\LoggerInterface;

class FixExportController extends Action
{
    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $fileFactory;

    /**
     * @var BaseHelper
     */
    private $baseHelper;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param Context $context
     * @param FileFactory $fileFactory
     * @param LoggerInterface $logger
     * @param BaseHelper $baseHelper
     */
    public function __construct(
        Context $context,
        FileFactory $fileFactory,
        LoggerInterface $logger,
        BaseHelper $baseHelper
    ) {
        $this->fileFactory = $fileFactory;
        $this->baseHelper  = $baseHelper;
        $this->logger      = $logger;
        parent::__construct($context);
    }

    /**
     * Load data with filter applying and create file for download.
     *
     * @param \Magento\ImportExport\Controller\Adminhtml\Export\Export $subject
     * @param \Closure $proceed
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function aroundExecute(\Magento\ImportExport\Controller\Adminhtml\Export\Export $subject, \Closure $proceed)
    {
        $params = $subject->getRequest()->getParams();

        if (!$this->baseHelper->checkModuleVersion('100.3.2', '', '>=', '<', 'Magento_ImportExport')
            || !isset($params['entity'])
            || $params['entity'] !== 'catalog_product_with_apo'
        ) {
            return $proceed();
        }

        if ($subject->getRequest()->getPost(ExportModel::FILTER_ELEMENT_GROUP)) {
            try {
                if (!array_key_exists('skip_attr', $params)) {
                    $params['skip_attr'] = [];
                }

                $exportInfoFactory = $this->_objectManager->get(
                    \Magento\ImportExport\Model\Export\Entity\ExportInfoFactory::class
                );
                $dataObject        = $exportInfoFactory->create(
                    $params['file_format'],
                    $params['entity'],
                    $params['export_filter'],
                    $params['skip_attr']
                );

                $consumer = $this->_objectManager->get(\Magento\ImportExport\Model\Export\Consumer::class);
                $consumer->process($dataObject);

                $this->messageManager->addSuccessMessage(
                    __(
                        'Message is added to queue, wait to get your file soon.'
                        . ' Make sure your cron job is running to export the file'
                    )
                );
            } catch (\Exception $e) {
                $this->logger->critical($e);
                $this->messageManager->addErrorMessage(__('Please correct the data sent value.'));
            }
        } else {
            $this->messageManager->addErrorMessage(__('Please correct the data sent value.'));
        }
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('adminhtml/*/index');
        return $resultRedirect;
    }

    /**
     * @return void
     */
    public function execute()
    {
        return;
    }
}
