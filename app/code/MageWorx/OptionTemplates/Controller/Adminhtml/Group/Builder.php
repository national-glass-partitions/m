<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionTemplates\Controller\Adminhtml\Group;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Registry;
use Magento\Store\Model\Store;
use MageWorx\OptionTemplates\Model\Group;
use MageWorx\OptionTemplates\Model\GroupFactory;
use Psr\Log\LoggerInterface as Logger;
use Magento\Store\Model\StoreFactory;

class Builder
{
    /**
     * @var \MageWorx\OptionTemplates\Model\GroupFactory
     */
    protected $groupFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var StoreFactory
     */
    protected $storeFactory;

    /**
     * @param GroupFactory $groupFactory
     * @param StoreFactory $storeFactory
     * @param Logger $logger
     * @param Registry $registry
     */
    public function __construct(
        GroupFactory $groupFactory,
        StoreFactory $storeFactory,
        Logger $logger,
        Registry $registry
    ) {
        $this->groupFactory = $groupFactory;
        $this->logger = $logger;
        $this->registry = $registry;
        $this->storeFactory = $storeFactory;
    }

    /**
     * Build group based on user request
     *
     * @param RequestInterface $request
     * @return \MageWorx\OptionTemplates\Model\Group
     */
    public function build(RequestInterface $request)
    {
        $groupId = (int)$request->getParam('group_id');
        $groupParam = $request->getParam('mageworx_optiontemplates_group');
        if (!$groupId && $groupParam) {
            $groupId = empty($groupParam['group_id']) ? null : $groupParam['group_id'];
        }

        /** @var $group \MageWorx\OptionTemplates\Model\Group */
        $group = $this->groupFactory->create();

        if ($groupId) {
            try {
                $group->load($groupId);
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
        } else {
            $group->unsetData($group->getIdFieldName());
            $group->setId(null);
        }

        $storeId = $request->getParam('store', Store::DEFAULT_STORE_ID);
        $store = $this->storeFactory->create()->load($storeId);
        $group->setStoreId($storeId);

        $this->registry->register('mageworx_optiontemplates_group', $group);
        $this->registry->register('current_store', $store);

        return $group;
    }
}
