<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionImportExport\Controller\Adminhtml\ImportExport;

use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Session as BackendSession;
use Psr\Log\LoggerInterface as Logger;
use MageWorx\OptionImportExport\Model\MageOne\ImportTemplateHandler as MageOneTemplateImportHandler;
use MageWorx\OptionImportExport\Model\MageOne\ImportOptionsHandler as MageOneOptionsImportHandler;
use MageWorx\OptionBase\Model\ActionMode;

class ImportMageOne extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'MageWorx_OptionImportExport::import_export';

    /**
     * @var MageOneTemplateImportHandler
     */
    protected $importTemplateHandler;

    /**
     * @var MageOneOptionsImportHandler
     */
    protected $importOptionsHandler;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var BackendSession
     */
    protected $backendSession;

    /**
     * @var ActionMode
     */
    protected $actionMode;

    /**
     * @param Context $context
     * @param MageOneTemplateImportHandler $importTemplateHandler
     * @param MageOneOptionsImportHandler $importOptionsHandler
     * @param Logger $logger
     * @param BackendSession $backendSession
     * @param ActionMode $actionMode
     */
    public function __construct(
        Context $context,
        Logger $logger,
        BackendSession $backendSession,
        MageOneTemplateImportHandler $importTemplateHandler,
        MageOneOptionsImportHandler $importOptionsHandler,
        ActionMode $actionMode
    ) {
        $this->importTemplateHandler = $importTemplateHandler;
        $this->importOptionsHandler  = $importOptionsHandler;
        $this->logger                = $logger;
        $this->backendSession        = $backendSession;
        $this->actionMode            = $actionMode;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        if ($this->getRequest()->isPost()) {
            $templatesOnlyFile = $this->getRequest()->getFiles('mageworx_mage_one_templates_only_file');
            $optionsOnlyFile   = $this->getRequest()->getFiles('mageworx_mage_one_options_only_file');
            $fullTemplatesFile = $this->getRequest()->getFiles('mageworx_mage_one_full_templates_file');
            $fullOptionsFile   = $this->getRequest()->getFiles('mageworx_mage_one_full_options_file');

            $this->actionMode->setActionMode(ActionMode::ACTION_IMPORT);
            $this->backendSession->setFileMagentoVersion('1');

            if ($fullTemplatesFile
                && !empty($fullTemplatesFile['tmp_name'])
                && $fullOptionsFile
                && !empty($fullOptionsFile['tmp_name'])
            ) {
                $this->backendSession->setImportMode('mage_one_full');
                $this->handleFullImport($fullOptionsFile, $fullTemplatesFile);
            } elseif ($templatesOnlyFile && !empty($templatesOnlyFile['tmp_name'])) {
                $this->backendSession->setImportMode('mage_one_template');
                $this->handleTemplatesOnlyImport($templatesOnlyFile);
            } elseif ($optionsOnlyFile && !empty($optionsOnlyFile['tmp_name'])) {
                $this->backendSession->setImportMode('mage_one_product');
                $this->handleOptionsOnlyImport($optionsOnlyFile);
            } else {
                $this->addInvalidFileMessage();
            }
        } else {
            $this->addInvalidFileMessage();
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRedirectUrl());
        return $resultRedirect;
    }

    /**
     * @param array $optionsFile
     * @param array $templatesFile
     * @return void
     */
    protected function handleFullImport($optionsFile, $templatesFile)
    {
        $map = $this->getRequest()->getParams();
        try {
            $this->importTemplateHandler->setFullImportMode();
            $this->importTemplateHandler->importFromFile($templatesFile, $map);
            $templateMap = $this->importTemplateHandler->getTemplateMap();
            $this->importOptionsHandler->importFromFile($optionsFile, $map, $templateMap);
            if ($this->importTemplateHandler->isSystemDataRequired()) {
                throw new \Magento\Framework\Exception\IntegrationException(
                    __("Please, link system specific data for Magento/Magento 2 and upload the file once again.")
                );
            }
            $this->clearTempVariables();
            $this->messageManager->addSuccessMessage(__('The option templates have been imported.'));
            $this->messageManager->addSuccessMessage(__('The product options have been imported.'));
            $this->processMissingImageFiles();
        } catch (\Magento\Framework\Exception\IntegrationException $e) {
            $this->addPossibleSystemDataMismatchMessage($e->getMessage());
        } catch (\Magento\Framework\Exception\FileSystemException $e) {
            $this->addMissingImagesMessage($e->getMessage());
        } catch (\Magento\Framework\Exception\NotFoundException $e) {
            $this->clearTempVariables();
            $this->messageManager->addSuccessMessage(__('The option templates have been imported.'));
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->addImportErrorMessage();
        } finally {
            $this->backendSession->setBeforeImportSystemStatus(
                $this->importOptionsHandler->getBeforeImportSystemStatus()
            );
            $this->backendSession->setStoreIds(
                array_replace(
                    $this->importTemplateHandler->getStoreIds(),
                    $this->importOptionsHandler->getStoreIds()
                )
            );
            $this->backendSession->setCustomerGroupIds(
                array_replace(
                    $this->importTemplateHandler->getCustomerGroupIds(),
                    $this->importOptionsHandler->getCustomerGroupIds()
                )
            );
        }
    }

    /**
     * @param array $file
     * @return void
     */
    protected function handleTemplatesOnlyImport($file)
    {
        $map = $this->getRequest()->getParams();
        try {
            $this->importTemplateHandler->importFromFile($file, $map);
            $this->clearTempVariables();
            $this->messageManager->addSuccessMessage(__('The option templates have been imported.'));
            $this->processMissingImageFiles();
        } catch (\Magento\Framework\Exception\IntegrationException $e) {
            $this->addPossibleSystemDataMismatchMessage($e->getMessage());
        } catch (\Magento\Framework\Exception\FileSystemException $e) {
            $this->addMissingImagesMessage($e->getMessage());
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->addImportErrorMessage();
        } finally {
            $this->backendSession->setStoreIds($this->importTemplateHandler->getStoreIds());
            $this->backendSession->setCustomerGroupIds($this->importTemplateHandler->getCustomerGroupIds());
        }
    }

    /**
     * @param array $file
     * @return void
     */
    protected function handleOptionsOnlyImport($file)
    {
        $map = $this->getRequest()->getParams();
        try {
            $this->importOptionsHandler->importFromFile($file, $map);
            $this->clearTempVariables();
            $this->messageManager->addSuccessMessage(__('The product options have been imported.'));
            $this->processMissingImageFiles();
        } catch (\Magento\Framework\Exception\IntegrationException $e) {
            $this->addPossibleSystemDataMismatchMessage($e->getMessage());
        } catch (\Magento\Framework\Exception\FileSystemException $e) {
            $this->addMissingImagesMessage($e->getMessage());
        } catch (\Magento\Framework\Exception\NotFoundException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->addImportErrorMessage();
        } finally {
            $this->backendSession->setBeforeImportSystemStatus(
                $this->importOptionsHandler->getBeforeImportSystemStatus()
            );
            $this->backendSession->setStoreIds($this->importOptionsHandler->getStoreIds());
            $this->backendSession->setCustomerGroupIds($this->importOptionsHandler->getCustomerGroupIds());
        }
    }

    /**
     * @return void
     */
    protected function addInvalidFileMessage()
    {
        $this->messageManager->addErrorMessage(__('Invalid file upload attempt'));
    }

    /**
     * @return void
     */
    protected function addImportErrorMessage()
    {
        $this->messageManager->addErrorMessage(__('Something goes wrong while templates import'));
    }

    /**
     * @param string $message
     * @return void
     */
    protected function addMissingImagesMessage($message)
    {
        $this->messageManager->addErrorMessage(
            $message
        );
        $this->messageManager->addErrorMessage(
            __(
                "Please, transfer Magento %1 MageWorx Advanced Product Options media folder %2 first or turn on 'Ignore missing images' setting in module configuration",
                '1',
                '(media/customoptions/)'
            )
        );
    }

    /**
     * @param string $message
     * @return void
     */
    protected function addPossibleSystemDataMismatchMessage($message)
    {
        $this->messageManager->addWarningMessage($message);
    }

    /**
     * @return void
     */
    protected function processMissingImageFiles()
    {
        $missingTemplateImages = $this->importTemplateHandler->getMissingImagesList();
        $missingProductImages  = $this->importOptionsHandler->getMissingImagesList();
        if ($missingTemplateImages || $missingProductImages) {
            $this->addMissingImagesListInLogMessage();
            foreach ($missingTemplateImages as $missingImage) {
                $this->logger->warning(__('Missing MageWorx image file') . ': pub/media/' . $missingImage);
            }
            foreach ($missingProductImages as $missingImage) {
                $this->logger->warning(__('Missing MageWorx image file') . ': pub/media/' . $missingImage);
            }
        }
    }

    /**
     * @return void
     */
    protected function addMissingImagesListInLogMessage()
    {
        $this->messageManager->addWarningMessage(
            __("You can find list of missing MageWorx image files in") . ' ' . 'var/log/system.log'
        );
    }

    /**
     * @return void
     */
    protected function clearTempVariables()
    {
        $this->backendSession->setStoreIds([]);
        $this->backendSession->setCustomerGroupIds([]);
        $this->backendSession->setImportMode('');
    }
}
