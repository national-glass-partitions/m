<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionBase\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use MageWorx\OptionBase\Helper\Data as Helper;
use MageWorx\OptionBase\Model\ResourceModel\ProductAttributes\CollectionFactory as CollectionFactory;

class ProductAttributes extends AbstractModel
{
    const TABLE_NAME                 = 'mageworx_optionbase_product_attributes';
    const OPTIONTEMPLATES_TABLE_NAME = 'mageworx_optiontemplates_group';

    const COLUMN_ENTITY_ID  = 'entity_id';
    const COLUMN_PRODUCT_ID = 'product_id';

    protected CollectionFactory $collectionFactory;
    protected Helper $helper;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     * @param Helper $helper
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        Helper $helper,
        Context $context,
        Registry $registry,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->helper            = $helper;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Set resource model and Id field name
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('MageWorx\OptionBase\Model\ResourceModel\ProductAttributes');
        $this->setIdFieldName('entity_id');
    }

    /**
     * Get item by product ID
     *
     * @param ProductInterface $product
     * @return ProductAttributes|null
     */
    public function getItemByProduct($product)
    {
        $id = $product->getData($this->helper->getLinkField());
        /** @var \MageWorx\OptionBase\Model\ResourceModel\ProductAttributes\Collection $attributesCollection */
        $collection = $this->collectionFactory->create();
        /** @var \MageWorx\OptionBase\Model\ProductAttributes $item */
        $collection->addFieldToFilter('product_id', $id);
        $item = $collection->getItemByColumnValue('product_id', $id);
        return $item;
    }
}
