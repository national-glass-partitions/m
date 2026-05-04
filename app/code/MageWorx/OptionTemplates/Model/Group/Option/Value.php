<?php
/**
 * Copyright © 2017 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionTemplates\Model\Group\Option;

use MageWorx\OptionBase\Helper\Data as BaseHelper;

/**
 * Catalog group option select type model
 *
 * @method \Magento\Catalog\Model\ResourceModel\Product\Option\Value _getResource()
 * @method \Magento\Catalog\Model\ResourceModel\Product\Option\Value getResource()
 * @method int getOptionId()
 * @method \Magento\Catalog\Model\Product\Option\Value setOptionId(int $value)
 *
 */
class Value extends \Magento\Catalog\Model\Product\Option\Value
{
    /**
     * @var BaseHelper
     */
    protected $baseHelper;

    /**
     *
     * @param BaseHelper $baseHelper
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \MageWorx\OptionTemplates\Model\ResourceModel\Group\Option\Value\CollectionFactory $valueCollectionFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        BaseHelper $baseHelper,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \MageWorx\OptionTemplates\Model\ResourceModel\Group\Option\Value\CollectionFactory $valueCollectionFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->baseHelper = $baseHelper;
        parent::__construct(
            $context,
            $registry,
            $valueCollectionFactory,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('MageWorx\OptionTemplates\Model\ResourceModel\Group\Option\Value');
    }

    /**
     * @param int $option_type_id
     * @return $this
     */
    public function deleteValues($option_type_id)
    {
        return $this;
    }
}
