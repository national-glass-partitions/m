<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionTemplates\Controller\Adminhtml\Group;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json as Serializer;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResponseInterface;
use MageWorx\OptionTemplates\Model\ResourceModel\Group\CollectionFactory;
use MageWorx\OptionTemplates\Model\Group\Copier;
use MageWorx\OptionTemplates\Model\Group;
use MageWorx\OptionBase\Model\Entity\Base as GroupBaseEntity;

class Export extends MassAction
{
    /**
     * @var FileFactory
     */
    protected $fileFactory;

    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * @var GroupBaseEntity
     */
    protected $groupBaseEntity;

    /**
     * @var Serializer
     */
    protected $serializer;

    /**
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param Copier $groupCopier
     * @param Builder $groupBuilder
     * @param Context $context
     * @param FileFactory $fileFactory
     * @param DirectoryList $directoryList
     * @param GroupBaseEntity $groupBaseEntity
     * @param Serializer $serializer
     */
    public function __construct(
        Filter $filter,
        CollectionFactory $collectionFactory,
        Copier $groupCopier,
        Builder $groupBuilder,
        Context $context,
        FileFactory $fileFactory,
        GroupBaseEntity $groupBaseEntity,
        DirectoryList $directoryList,
        Serializer $serializer
    ) {
        $this->fileFactory     = $fileFactory;
        $this->directoryList   = $directoryList;
        $this->groupBaseEntity = $groupBaseEntity;
        $this->serializer      = $serializer;
        parent::__construct($filter, $collectionFactory, $groupBuilder, $context);
    }

    /**
     * Execute action
     *
     * @return ResponseInterface|\Magento\Backend\Model\View\Result\Redirect
     * @return
     */
    public function execute()
    {
        $fileName = 'mageworx_option_templates.json';

        try {
            $groupsData = [];
            $collection = $this->filter->getCollection($this->collectionFactory->create());

            foreach ($collection as $group) {
                $groupsData[$group->getGroupId()] = $this->doTheAction($group);
            }

            return $this->fileFactory->create(
                $fileName,
                $this->serializer->serialize($groupsData),
                DirectoryList::VAR_DIR,
                'application/json'
            );
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('An error occurred while exporting template(s).'));
        }

        $redirectResult = $this->resultRedirectFactory->create();
        $redirectResult->setPath('mageworx_optiontemplates/group/index');

        return $redirectResult;
    }

    /**
     * @param Group $group
     * @return $this
     */
    protected function doTheAction(Group $group)
    {
        $group->setData('options', $this->groupBaseEntity->getOptionsAsArray($group));
        $group->setData('assigned_product_skus', $group->getResource()->getProductSku($group));
        return $group->getData();
    }
}
