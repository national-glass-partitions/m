<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionTemplates\Controller\Adminhtml\Group;

use Magento\Framework\Controller\ResultFactory;

class Delete extends \MageWorx\OptionTemplates\Controller\Adminhtml\Group
{
    /**
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $group = $this->groupBuilder->build($this->getRequest());
        $group->delete();

        $this->messageManager->addSuccessMessage(
            __('The option template has been deleted.')
        );

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('mageworx_optiontemplates/*/index');
    }
}
