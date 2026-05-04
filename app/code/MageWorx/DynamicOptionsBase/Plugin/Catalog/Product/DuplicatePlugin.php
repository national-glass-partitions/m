<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\DynamicOptionsBase\Plugin\Catalog\Product;

class DuplicatePlugin
{
    protected \MageWorx\DynamicOptionsBase\Model\ResourceModel\DynamicOption $resource;

    /**
     * DuplicatePlugin constructor.
     *
     * @param \MageWorx\DynamicOptionsBase\Model\ResourceModel\DynamicOption $resource
     */
    public function __construct(
        \MageWorx\DynamicOptionsBase\Model\ResourceModel\DynamicOption $resource
    ) {
        $this->resource = $resource;
    }

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Product $subject
     * @param \Magento\Catalog\Model\ResourceModel\Product $result
     * @param int $oldId
     * @param int $newId
     * @return \Magento\Catalog\Model\ResourceModel\Product
     */
    public function afterDuplicate(
        \Magento\Catalog\Model\ResourceModel\Product $subject,
        $result,
        $oldId,
        $newId
    ) {
        $this->resource->duplicate($oldId, $newId);

        return $result;
    }
}
