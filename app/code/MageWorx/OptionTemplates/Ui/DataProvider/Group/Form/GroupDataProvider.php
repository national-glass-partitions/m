<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionTemplates\Ui\DataProvider\Group\Form;

use Magento\Ui\DataProvider\AbstractDataProvider;
use MageWorx\OptionTemplates\Model\ResourceModel\Group\CollectionFactory as GroupCollectionFactory;
use MageWorx\OptionTemplates\Model\ResourceModel\Group\Collection as GroupCollection;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use Magento\Ui\DataProvider\Modifier\ModifierInterface as Modifier;

/**
 * Class GroupDataProvider
 * @package MageWorx\OptionTemplates\Ui\DataProvider\Group\Form
 */
class GroupDataProvider extends AbstractDataProvider
{
    /**
     * @var GroupCollection
     */
    protected $collection;

    /** @var PoolInterface */
    protected $pool;

    /**
     * Group Data Provider constructor
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param GroupCollectionFactory $collectionFactory
     * @param PoolInterface $pool
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        GroupCollectionFactory $collectionFactory,
        PoolInterface $pool,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->pool = $pool;
        /** @var GroupCollection collection */
        $this->collection = $collectionFactory->create();
    }

    /**
     * Get meta from all of the modifiers
     *
     * @return array
     */
    public function getMeta()
    {
        /**
         * @var Modifier $modifier
         */
        foreach ($this->pool->getModifiersInstances() as $modifier) {
            $metaBeforeModification = $this->meta;
            $this->meta = $modifier->modifyMeta($metaBeforeModification);
        }
        $meta = $this->meta;

        return $meta;
    }

    /**
     * Get data from all of the modifiers
     *
     * @return array|null
     */
    public function getData()
    {
        /**
         * @var Modifier $modifier
         */
        foreach ($this->pool->getModifiersInstances() as $modifier) {
            $this->data = $modifier->modifyData($this->data);
        }
        $data = $this->prepareData($this->data);

        return $data;
    }

    /**
     * Prepare data before return it to the form
     *
     * @param null $data
     * @return array|null
     */
    protected function prepareData($data = null)
    {
        if (!$data) {
            $data = $this->data;
        }

        return $data;
    }
}
