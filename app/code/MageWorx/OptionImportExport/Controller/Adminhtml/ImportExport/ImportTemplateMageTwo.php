<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionImportExport\Controller\Adminhtml\ImportExport;

use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Session as BackendSession;
use MageWorx\OptionImportExport\Model\MageTwo\ImportTemplateHandler as ImportTemplateHandler;
use Psr\Log\LoggerInterface as Logger;
use MageWorx\OptionBase\Model\ActionMode;
use Magento\Store\Model\StoreManagerInterface;

class ImportTemplateMageTwo extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'MageWorx_OptionImportExport::import_export';

    /**
     * @var ImportTemplateHandler
     */
    protected $importTemplateHandler;

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
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param Context $context
     * @param ImportTemplateHandler $importTemplateHandler
     * @param Logger $logger
     * @param BackendSession $backendSession
     * @param ActionMode $actionMode
     */
    public function __construct(
        Context $context,
        Logger $logger,
        BackendSession $backendSession,
        ImportTemplateHandler $importTemplateHandler,
        ActionMode $actionMode,
        StoreManagerInterface $storeManager
    ) {
        $this->importTemplateHandler = $importTemplateHandler;
        $this->logger                = $logger;
        $this->backendSession        = $backendSession;
        $this->actionMode            = $actionMode;
        $this->storeManager          = $storeManager;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        if ($this->getRequest()->isPost()) {

            $file = $this->getRequest()->getFiles('mageworx_mage_two_templates_only_file');
            $map  = $this->getRequest()->getParams();

            $storeManagerDataList = $this->storeManager->getStores();
            $storeData            = [];
            foreach ($storeManagerDataList as $store) {
                $storeData[$store['store_id']] = $store['store_id'];
            }
            $map['mageworx_optiontemplates_import_from_stores'] = $storeData;

            if ($file && !empty($file['tmp_name'])) {
                $this->actionMode->setActionMode(ActionMode::ACTION_IMPORT);
                $this->backendSession->setFileMagentoVersion('2');
                $this->backendSession->setImportMode('mage_two_template');
                try {
                    $this->importTemplateHandler->importFromFile($file, $map);
                    $this->messageManager->addSuccessMessage(__('The option templates have been imported.'));
                    $this->clearTempVariables();
                    $this->processMissingImageFiles();
                } catch (\Magento\Framework\Exception\IntegrationException $e) {
                    $this->addPossibleSystemDataMismatchMessage($e->getMessage());
                } catch (\Magento\Framework\Exception\FileSystemException $e) {
                    $this->addMissingImagesMessage($e->getMessage());
                } catch (\Magento\Framework\Exception\InputException $e) {
                    $this->addApplyTemplatesMessage();
                } catch (\Magento\Framework\Exception\LocalizedException $e) {
                    $this->messageManager->addErrorMessage($e->getMessage());
                } catch (\Exception $e) {
                    $this->logger->critical($e->getMessage());
                    $this->addImportErrorMessage();
                } finally {
                    $this->backendSession->setStoreIds($this->importTemplateHandler->getStoreIds());
                    $this->backendSession->setCustomerGroupIds($this->importTemplateHandler->getCustomerGroupIds());
                    $this->backendSession->setMissingSkus($this->importTemplateHandler->getMissingSkus());
                    $this->backendSession->setAssignedProducts($this->importTemplateHandler->getAssignedProducts());
                }
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
                '2',
                '(pub/media/mageworx/)'
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
        $missingImages = $this->importTemplateHandler->getMissingImagesList();
        if ($missingImages) {
            $this->addMissingImagesListInLogMessage();
            foreach ($missingImages as $missingImage) {
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
    protected function addApplyTemplatesMessage()
    {
        $missingSkus = $this->importTemplateHandler->getMissingSkus();
        if ($missingSkus) {
            $this->logger->warning(__('Missing Products') . ': ' . '"' . implode('", "', $missingSkus) . '"');
            if (count($missingSkus) > 2) {
                $preparedMissingProductsWarning = __(
                    '"%1", "%2" + %3 more',
                    array_shift($missingSkus),
                    array_shift($missingSkus),
                    count($missingSkus)
                );
            } else {
                $preparedMissingProductsWarning = '"' . implode('", "', $missingSkus) . '"';
            }

            $this->messageManager->addWarningMessage(
                __("Some assigned products are missing: %1", $preparedMissingProductsWarning)
            );
        } else {
            $this->messageManager->addWarningMessage(
                __("Please, select template applying mode to continue.")
            );
        }
    }

    /**
     * @return void
     */
    protected function clearTempVariables()
    {
        $this->backendSession->setCanSkipTemplatesApplying(false);
        $this->backendSession->setStoreIds([]);
        $this->backendSession->setCustomerGroupIds([]);
        $this->backendSession->setMissingSkus([]);
        $this->backendSession->setAssignedProducts([]);
        $this->backendSession->setImportMode('');
    }
}
