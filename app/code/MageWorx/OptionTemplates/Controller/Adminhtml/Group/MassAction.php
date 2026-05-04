<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionTemplates\Controller\Adminhtml\Group;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;
use MageWorx\OptionTemplates\Controller\Adminhtml\Group as GroupController;
use MageWorx\OptionTemplates\Model\Group as GroupModel;
use MageWorx\OptionTemplates\Model\ResourceModel\Group\CollectionFactory;

abstract class MassAction extends GroupController
{
    /**
     *
     * @var Filter
     */
    protected $filter;

    /**
     *
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var string
     */
    protected $successMessage = 'Mass Action successful on %1 records';

    /**
     * @var string
     */
    protected $errorMessage = 'Mass Action failed';

    /**
     *
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param Builder $groupBuilder
     * @param Context $context
     */
    public function __construct(
        Filter $filter,
        CollectionFactory $collectionFactory,
        Builder $groupBuilder,
        Context $context
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        parent::__construct($groupBuilder, $context);
    }

    /**
     *
     * @param GroupModel $group
     * @return mixed
     */
    abstract protected function doTheAction(GroupModel $group);

    /**
     * Execute action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $collectionSize = $collection->getSize();

            foreach ($collection as $group) {
                $this->doTheAction($group);
            }
            $this->messageManager->addSuccessMessage(__($this->successMessage, $collectionSize));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __($this->errorMessage));
        }
        $redirectResult = $this->resultRedirectFactory->create();
        $redirectResult->setPath('mageworx_optiontemplates/group/index');

        return $redirectResult;
    }
}
