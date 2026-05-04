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

use Magento\Framework\Controller\ResultFactory;

class MassDuplicate extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Bss_DupCmsPageBlock::page_duplicate';

    /**
     * @var /Magento\Ui\Component\MassAction\Filter
     */
    protected $filter;

    /**
     * @var /Magento\Cms\Model\ResourceModel\Page\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var \Magento\Cms\Model\PageFactory
     */
    protected $pageModel;

    /**
     * @var \Magento\Cms\Model\PageRepository
     */
    protected $pageRepository;

    /**
     * @var \Bss\DupCmsPageBlock\Helper\Data
     */
    protected $helperBss;

    /**
     * MassDuplicate constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Ui\Component\MassAction\Filter $filter
     * @param \Magento\Cms\Model\ResourceModel\Page\CollectionFactory $collectionFactory
     * @param \Bss\DupCmsPageBlock\Helper\Data $helperBss
     * @param \Magento\Cms\Model\PageFactory $pageModel
     * @param \Magento\Cms\Model\PageRepository $pageRepository
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Ui\Component\MassAction\Filter $filter,
        \Magento\Cms\Model\ResourceModel\Page\CollectionFactory $collectionFactory,
        \Bss\DupCmsPageBlock\Helper\Data $helperBss,
        \Magento\Cms\Model\PageFactory $pageModel,
        \Magento\Cms\Model\PageRepository $pageRepository
    ) {
        $this->pageModel = $pageModel;
        $this->collectionFactory = $collectionFactory;
        $this->helperBss = $helperBss;
        $this->pageRepository = $pageRepository;
        $this->filter = $filter;
        parent::__construct($context);
    }

    /**
     * @param $page
     */
    private function getPage($page)
    {
        try {
            $page->save();
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__("This page can not be duplicated"));
        }
    }

    /**
     * Duplicate CMS page
     *
     * @return \Magento\Framework\Controller\ResultInterface
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $collectionSize = $collection->getSize();

        foreach ($collection as $page) {
            $id = $page->getId();
            try {
                $model = $this->pageRepository->getById($id);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $model = null;
            }
            $duplicatePage = $this->pageModel->create();

            $cmsPageData = $this->helperBss->duplicate($id, $model, 'page');
            if (!$cmsPageData) {
                $this->messageManager->addErrorMessage(__('This page no longer exists.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }

            $duplicatePage->setData($cmsPageData);
            $this->getPage($duplicatePage);
        }

        $this->messageManager->addSuccessMessage(__('A total of %1 record(s) have been duplicated.', $collectionSize));

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('cms/page/index');
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Bss_DupCmsPageBlock::duplicate_cmspage_mass');
    }
}
