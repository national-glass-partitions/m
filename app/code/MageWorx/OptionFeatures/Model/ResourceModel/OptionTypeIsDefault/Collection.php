<?php
/**
 * Copyright © 2018 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionFeatures\Model\ResourceModel\OptionTypeIsDefault;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Set resource model and determine field mapping
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'MageWorx\OptionFeatures\Model\OptionTypeIsDefault',
            'MageWorx\OptionFeatures\Model\ResourceModel\OptionTypeIsDefault'
        );
    }
}
