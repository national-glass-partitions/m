<?php

/**
 * Copyright © 2018 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionBase\Plugin;

use Magento\Catalog\Api\Data\ProductInterface;
use MageWorx\OptionBase\Helper\Data as Helper;
use MageWorx\OptionBase\Model\ResourceModel\CollectionUpdaterRegistry;

class CollectEnterpriseProductConditions
{
    private Helper $helper;
    private CollectionUpdaterRegistry $collectionUpdaterRegistry;

    public function __construct(
        CollectionUpdaterRegistry $collectionUpdaterRegistry,
        Helper $helper
    ) {
        $this->collectionUpdaterRegistry = $collectionUpdaterRegistry;
        $this->helper = $helper;
    }

    /**
     * @param \Magento\Catalog\Model\Product\Option\Repository $object
     * @param ProductInterface $product
     * @param bool $requiredOnly
     * @return array
     */
    public function beforeGetProductOptions($object, ProductInterface $product, $requiredOnly = false)
    {
        if ($this->helper->isEnterprise()) {
            $this->collectionUpdaterRegistry->setCurrentRowIds([$product->getRowId()]);
        } else {
            $this->collectionUpdaterRegistry->setCurrentRowIds([]);
        }
        return [$product, $requiredOnly];
    }
}
