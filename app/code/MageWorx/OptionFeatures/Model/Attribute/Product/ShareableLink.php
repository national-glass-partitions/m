<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionFeatures\Model\Attribute\Product;

use MageWorx\OptionFeatures\Helper\Data as Helper;
use MageWorx\OptionBase\Model\Product\AbstractProductAttribute;
use MageWorx\OptionBase\Api\ProductAttributeInterface;

class ShareableLink extends AbstractProductAttribute implements ProductAttributeInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return Helper::KEY_SHAREABLE_LINK;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriorityValue()
    {
        return Helper::SHAREABLE_LINK_ENABLED;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultValue()
    {
        return Helper::SHAREABLE_LINK_USE_CONFIG;
    }
}
