<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionFeatures\Model\Attribute\Product;

use Magento\Framework\DataObjectFactory;
use Magento\Framework\App\ResourceConnection;
use MageWorx\OptionFeatures\Helper\Data as Helper;
use MageWorx\OptionBase\Model\Product\AbstractProductAttribute;
use MageWorx\OptionBase\Api\ProductAttributeInterface;

class AbsolutePrice extends AbstractProductAttribute implements ProductAttributeInterface
{
    protected Helper $helper;

    /**
     * @param ResourceConnection $resource
     * @param Helper $helper
     * @param DataObjectFactory $dataObjectFactory
     */
    public function __construct(
        Helper $helper,
        ResourceConnection $resource,
        DataObjectFactory $dataObjectFactory
    ) {
        $this->helper   = $helper;
        $this->resource = $resource;
        parent::__construct($resource, $dataObjectFactory);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return Helper::KEY_ABSOLUTE_PRICE;
    }

    /**
     * Get default value of attribute
     *
     * @return int
     */
    public function getDefaultValue()
    {
        return (int)$this->helper->isAbsolutePriceEnabledByDefault();
    }
}
