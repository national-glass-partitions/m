<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionTemplates\Controller\Adminhtml\Group;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Session as BackendSession;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use MageWorx\OptionBase\Helper\Data as BaseHelper;

class Edit extends \MageWorx\OptionTemplates\Controller\Adminhtml\Group
{
    /**
     * Backend session
     *
     * @var BackendSession
     */
    protected $backendSession;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var BaseHelper
     */
    protected $baseHelper;

    /**
     * Construct
     *
     * @param BaseHelper $baseHelper
     * @param Builder $groupBuilder
     * @param PageFactory $resultPageFactory
     * @param Context $context
     * @param Registry $registry
     */
    public function __construct(
        BaseHelper $baseHelper,
        Builder $groupBuilder,
        PageFactory $resultPageFactory,
        Context $context,
        Registry $registry
    ) {
        $this->baseHelper = $baseHelper;
        $this->backendSession = $context->getSession();
        $this->resultPageFactory = $resultPageFactory;
        $this->registry = $registry;

        parent::__construct($groupBuilder, $context);
    }

    /**
     * Is action allowed
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('MageWorx_OptionTemplates::groups');
    }

    public function execute()
    {
        /** @var \MageWorx\OptionTemplates\Model\Group $group */
        $group = $this->groupBuilder->build($this->getRequest());
        $this->registry->register('current_product', $group);

        /** @var \Magento\Backend\Model\View\Result\Page|\Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('MageWorx_OptionTemplates::groups');
        $resultPage->getConfig()->getTitle()->set((__('Options Template')));

        $groupId = $this->getRequest()->getParam('group_id');

        if ($groupId && !$group->getId()) {
            $this->messageManager->addErrorMessage(__('The options template no longer exists.'));
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath(
                'mageworx_optiontemplates/*/edit',
                [
                    'group_id' => $group->getId(),
                    '_current' => true,
                ]
            );

            return $resultRedirect;
        }

        $title = $group->getId() ? $group->getName() : __('New Options Template');
        $resultPage->getConfig()->getTitle()->append($title);
        $data = $this->backendSession->getData('mageworx_optiontemplates_group_data', true);
        if (!empty($data)) {
            $group->setData($data);
        }

        return $resultPage;
    }
}
