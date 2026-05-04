<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\GeoIP\Controller\Adminhtml\Relations;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    const ADMIN_RESOURCE = 'MageWorx_GeoIP::geoip_relations_grid';

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Backend\Model\View\Result\Page|null
     */
    protected $resultPage = null;

    /**
     * Index constructor.
     *
     * @param PageFactory $resultPageFactory
     * @param Context $context
     */
    public function __construct(
        PageFactory $resultPageFactory,
        Context $context
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * Set page data
     *
     * @return $this
     */
    public function setPageData(): Index
    {
        $resultPage = $this->getResultPage();
        $resultPage->getConfig()->getTitle()->set(__('MageWorx Region Relations'));

        return $this;
    }

    /**
     * Instantiate result page object
     *
     * @return \Magento\Backend\Model\View\Result\Page|\Magento\Framework\View\Result\Page
     */
    public function getResultPage()
    {
        if ($this->resultPage === null) {
            $this->resultPage = $this->resultPageFactory->create();
        }

        return $this->resultPage;
    }

    /**
     * Init action
     *
     * @return $this
     */
    public function _initAction(): Index
    {
        $this->_view->loadLayout();
        $this->_setActiveMenu(
            'MageWorx_GeoIP::geoip_relations_grid'
        )->_addBreadcrumb(
            __('Region'),
            __('Relations')
        );

        return $this;
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Page|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $this->setPageData();

        return $this->getResultPage();
    }
}
