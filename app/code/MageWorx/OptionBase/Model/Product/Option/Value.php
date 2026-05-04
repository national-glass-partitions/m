<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MageWorx\OptionBase\Model\Product\Option;

use Magento\Catalog\Model\ResourceModel\Product\Option\Value\CollectionFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Api\ExtensionAttributesInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use MageWorx\OptionBase\Api\Data\ProductCustomOptionValuesExtensionInterface;
use MageWorx\OptionBase\Api\Data\ProductCustomOptionValuesInterface;

/**
 * Catalog product option select type model
 *
 * @api
 * */
class Value extends \Magento\Catalog\Model\Product\Option\Value implements ProductCustomOptionValuesInterface
{
    /**
     * Value collection factory
     *
     * @var CollectionFactory
     */
    protected $_valueCollectionFactory;

    protected ExtensionAttributesFactory $extensionAttributesFactory;
    protected ExtensionAttributesInterface $extensionAttributes;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param CollectionFactory $valueCollectionFactory
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        CollectionFactory $valueCollectionFactory,
        ExtensionAttributesFactory $extensionFactory,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $valueCollectionFactory,
            $resource,
            $resourceCollection,
            $data
        );

        $this->extensionAttributesFactory = $extensionFactory;

        if (isset($data[self::EXTENSION_ATTRIBUTES_KEY]) && is_array($data[self::EXTENSION_ATTRIBUTES_KEY])) {
            $this->populateExtensionAttributes($data[self::EXTENSION_ATTRIBUTES_KEY]);
        }
    }

    /**
     * Retrieve existing extension attributes object.
     *
     * @return ProductCustomOptionValuesExtensionInterface|null
     */
    public function getExtensionAttributes() {
        return $this->_getExtensionAttributes();
    }

    /**
     * Set an extension attributes object.
     *
     * @param ProductCustomOptionValuesExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        ProductCustomOptionValuesExtensionInterface $extensionAttributes
    ) {
        return $this->_setExtensionAttributes($extensionAttributes);
    }

    /**
     * Identifier setter
     *
     * @param mixed $value
     * @return $this
     */
    public function setId($value)
    {
        parent::setId($value);
        return $this->setData('id', $value);
    }

    /**
     * Set an extension attributes object.
     *
     * @param ExtensionAttributesInterface $extensionAttributes
     * @return $this
     */
    protected function _setExtensionAttributes(ExtensionAttributesInterface $extensionAttributes)
    {
        $this->_data[self::EXTENSION_ATTRIBUTES_KEY] = $extensionAttributes;
        return $this;
    }

    /**
     * Retrieve existing extension attributes object or create a new one.
     *
     * @return ExtensionAttributesInterface
     */
    protected function _getExtensionAttributes()
    {
        if (!$this->getData(self::EXTENSION_ATTRIBUTES_KEY)) {
            $this->populateExtensionAttributes([]);
        }
        return $this->getData(self::EXTENSION_ATTRIBUTES_KEY);
    }

    /**
     * Instantiate extension attributes object and populate it with the provided data.
     *
     * @param array $extensionAttributesData
     * @return void
     */
    private function populateExtensionAttributes(array $extensionAttributesData = [])
    {
        $extensionAttributes = $this->extensionAttributesFactory->create(get_class($this), $extensionAttributesData);
        $this->_setExtensionAttributes($extensionAttributes);
    }

    /**
     * @inheritdoc
     */
    public function __sleep()
    {
        return array_diff(parent::__sleep(), ['extensionAttributesFactory']);
    }

    /**
     * @inheritdoc
     */
    public function __wakeup()
    {
        parent::__wakeup();
        $objectManager = ObjectManager::getInstance();
        $this->extensionAttributesFactory = $objectManager->get(ExtensionAttributesFactory::class);
    }
}
