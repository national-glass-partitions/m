<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionTemplates\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\RedirectFactory;

abstract class Group extends \Magento\Backend\App\Action
{
    /**
     * @var RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var Group\Builder
     */
    protected $groupBuilder;

    /**
     *
     * @param \MageWorx\OptionTemplates\Controller\Adminhtml\Group\Builder $groupBuilder
     * @param Context $context
     */
    public function __construct(
        \MageWorx\OptionTemplates\Controller\Adminhtml\Group\Builder $groupBuilder,
        Context $context
    ) {
        $this->groupBuilder = $groupBuilder;
        $this->resultRedirectFactory = $context->getResultRedirectFactory();
        parent::__construct($context);
    }

    /**
     * @return array|mixed
     */
    public function getProducts()
    {
        if (!$this->getId()) {
            return [];
        }
        $array = $this->getData('products');
        if ($array === null) {
            $array = $this->getResource()->getProducts($this);
            $this->setData('products', $array);
        }

        return $array;
    }

    /**
     * Is access to section allowed
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('MageWorx_OptionTemplates::groups');
    }
}
