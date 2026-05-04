<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionTemplates\Controller\Adminhtml\Group;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use MageWorx\OptionTemplates\Controller\Adminhtml\Group as GroupController;
use MageWorx\OptionTemplates\Controller\Adminhtml\Group\Builder as GroupBuilder;
use MageWorx\OptionTemplates\Model\Group;
use MageWorx\OptionTemplates\Model\GroupFactory;

class InlineEdit extends GroupController
{
    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var \MageWorx\OptionTemplates\Model\GroupFactory
     */
    protected $groupFactory;

    /**
     *
     * @param JsonFactory $jsonFactory
     * @param GroupFactory $groupFactory
     * @param Builder $groupBuilder
     * @param Context $context
     */
    public function __construct(
        JsonFactory $jsonFactory,
        GroupFactory $groupFactory,
        GroupBuilder $groupBuilder,
        Context $context
    ) {
        $this->groupFactory = $groupFactory;
        $this->jsonFactory = $jsonFactory;
        parent::__construct($groupBuilder, $context);
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->jsonFactory->create();
        $error = false;
        $messages = [];

        $postItems = $this->getRequest()->getParam('items', []);
        if (!($this->getRequest()->getParam('isAjax') && count($postItems))) {
            return $resultJson->setData([
                'messages' => [__('Please correct the data sent.')],
                'error' => true,
            ]);
        }

        foreach (array_keys($postItems) as $groupId) {
            /** @var \MageWorx\OptionTemplates\Model\Group $group */
            $group = $this->groupFactory->create()->load($groupId);
            try {
                $groupData = $this->filterData($postItems[$groupId]);
                $group->addData($groupData);
                $group->save();
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $messages[] = $this->getErrorWithGroupId($group, $e->getMessage());
                $error = true;
            } catch (\RuntimeException $e) {
                $messages[] = $this->getErrorWithGroupId($group, $e->getMessage());
                $error = true;
            } catch (\Exception $e) {
                $messages[] = $this->getErrorWithGroupId(
                    $group,
                    __('Something went wrong while saving the page.')
                );
                $error = true;
            }
        }

        return $resultJson->setData([
            'messages' => $messages,
            'error' => $error,
        ]);
    }

    /**
     * Add group id to error message
     *
     * @param Group $group
     * @param string $errorText
     * @return string
     */
    protected function getErrorWithGroupId(Group $group, $errorText)
    {
        return '[Template ID: ' . $group->getId() . '] ' . $errorText;
    }

    /**
     *
     * @param array $data
     * @return array
     */
    protected function filterData(array $data)
    {
        return $data;
    }
}
