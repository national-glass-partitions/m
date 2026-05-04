<?php

namespace MageWorx\OptionTemplates\Model\ResourceModel\Group\Option\Value;

/**
 * Factory class for @see
 * \Magento\Catalog\Model\ResourceModel\Product\Option\Value\Collection
 */
class CollectionFactory extends \Magento\Catalog\Model\ResourceModel\Product\Option\Value\CollectionFactory
{
    /**
     * Object Manager instance
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager = null;

    /**
     * Instance name to create
     *
     * @var string
     */
    protected $_instanceName = null;

    /**
     * Factory constructor
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param string $instanceName
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        $instanceName = '\\MageWorx\\OptionTemplates\\Model\\ResourceModel\\Group\\Option\\Value\\Collection'
    ) {
        parent::__construct($objectManager, $instanceName);
    }

    /**
     * Create class instance with specified parameters
     *
     * @param array $data
     * @return \Magento\Catalog\Model\ResourceModel\Product\Option\Value\Collection
     */
    public function create(array $data = [])
    {
        return $this->_objectManager->create($this->_instanceName, $data);
    }
}
