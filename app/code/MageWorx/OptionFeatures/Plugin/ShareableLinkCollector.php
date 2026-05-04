<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionFeatures\Plugin;

use Magento\Catalog\Controller\Product\View as ViewController;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\ObjectManagerInterface as ObjectManager;
use MageWorx\OptionBase\Model\InjectedClasses;

class ShareableLinkCollector
{
    protected InjectedClasses $injectedClasses;
    protected ProductRepositoryInterface $productRepository;
    protected StoreManagerInterface $storeManager;
    protected ObjectManager $objectManager;

    public function __construct(
        InjectedClasses $injectedClasses,
        ObjectManager $objectManager,
        ProductRepositoryInterface $productRepository,
        StoreManagerInterface $storeManager
    ) {
        $this->injectedClasses   = $injectedClasses;
        $this->objectManager     = $objectManager;
        $this->productRepository = $productRepository;
        $this->storeManager      = $storeManager;
    }

    /**
     * Collect selected values from shareable link
     *
     * @example $config must fit "[optionID]-[valueID],..,[optionID]-[valueID]-[valueID],.." format
     *
     * @param ViewController $subject
     * @return void
     */
    public function beforeExecute($subject)
    {
        $productId = (int)$subject->getRequest()->getParam('id');
        $config    = $subject->getRequest()->getParam('config');

        if (!$productId || !$config) {
            return;
        }

        $hiddenDependents = $this->injectedClasses->getData('hidden_dependents');
        if (!$hiddenDependents) {
            return;
        }

        try {
            $product = $this->productRepository->getById($productId, false, $this->storeManager->getStore()->getId());
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return;
        }

        $selectedValues          = [];
        $optionIdToValueIdsArray = explode(',', $config);
        foreach ($optionIdToValueIdsArray as $optionIdValueIdString) {
            if (!$optionIdValueIdString) {
                continue;
            }
            $ids = explode('-', $optionIdValueIdString);
            if (!$ids) {
                continue;
            }
            $selectedValues = array_merge($selectedValues, array_slice($ids, 1));
        }

        $hiddenDependents->calculateHiddenDependents($product, $selectedValues);

        return;
    }
}
