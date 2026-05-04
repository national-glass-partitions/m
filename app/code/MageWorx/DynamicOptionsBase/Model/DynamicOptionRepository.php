<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\DynamicOptionsBase\Model;

use MageWorx\DynamicOptionsBase\Api\Data\DynamicOptionInterface;
use MageWorx\DynamicOptionsBase\Api\DynamicOptionRepositoryInterface;
use MageWorx\DynamicOptionsBase\Model\DynamicOptionFactory;
use MageWorx\DynamicOptionsBase\Model\ResourceModel\DynamicOption as ResourceDynamicOption;
use MageWorx\DynamicOptionsBase\Model\ResourceModel\DynamicOption\Collection as DynamicOptionCollection;
use MageWorx\DynamicOptionsBase\Model\ResourceModel\DynamicOption\CollectionFactory;
use Magento\Framework\Exception\NoSuchEntityException;

class DynamicOptionRepository implements DynamicOptionRepositoryInterface
{
    private ResourceDynamicOption $resource;
    private \MageWorx\DynamicOptionsBase\Model\DynamicOptionFactory $dynamicOptionFactory;
    private CollectionFactory $dynamicOptionCollectionFactory;
    private array $collectionCache = [];
    private array $idsCache = [];

    /**
     * DynamicOptionRepository constructor.
     *
     * @param CollectionFactory $dynamicOptionCollectionFactory
     * @param ResourceDynamicOption $resource
     * @param DynamicOptionFactory $dynamicOptionFactory
     */
    public function __construct(
        CollectionFactory $dynamicOptionCollectionFactory,
        ResourceDynamicOption $resource,
        DynamicOptionFactory $dynamicOptionFactory
    ) {
        $this->resource                       = $resource;
        $this->dynamicOptionFactory           = $dynamicOptionFactory;
        $this->dynamicOptionCollectionFactory = $dynamicOptionCollectionFactory;
    }

    /**
     * Get empty Dynamic option
     *
     * @return DynamicOptionInterface
     */
    public function getEmptyEntity(): DynamicOptionInterface
    {
        return $this->dynamicOptionFactory->create();
    }

    /**
     * Save DynamicOption
     *
     * @param DynamicOptionInterface $dynamicOption
     *
     * @return DynamicOptionInterface
     *
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(DynamicOptionInterface $dynamicOption): DynamicOptionInterface
    {
        try {
            $this->resource->save($dynamicOption);
        } catch (\Exception $exception) {
            throw new  \Magento\Framework\Exception\CouldNotSaveException(
                __(
                    'Could not save the dynamic option: %1',
                    $exception->getMessage()
                )
            );
        }

        return $dynamicOption;
    }

    /**
     * Retrieve DynamicOption.
     *
     * @param int $id
     *
     * @return DynamicOptionInterface $dynamicOption
     * @throws NoSuchEntityException
     */
    public function getById(int $id): DynamicOptionInterface
    {
        /** @var DynamicOptionInterface $dynamicOption */
        $dynamicOption = $this->dynamicOptionFactory->create();

        $this->resource->load($dynamicOption, $id, 'option_id');

        if (!$dynamicOption->getId()) {
            throw new NoSuchEntityException(__('Entity with option id "%1" does not exist.', $id));
        }

        return $dynamicOption;
    }

    /**
     * Delete DynamicOption
     *
     * @param DynamicOptionInterface $dynamicOption
     *
     * @return bool
     *
     * @throws CouldNotDeleteException
     */
    public function delete(DynamicOptionInterface $dynamicOption): bool
    {
        try {
            $this->resource->delete($dynamicOption);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(
                __(
                    'Could not delete the dynamic option: %1',
                    $exception->getMessage()
                )
            );
        }

        return true;
    }

    /**
     * Delete Dynamic Options by given Identity
     *
     * @param int $id
     *
     * @return bool
     *
     * @throws CouldNotDeleteException
     */
    public function deleteById(int $id): bool
    {
        return $this->delete($this->getById((int)$id));
    }

    public function deleteByProductId(int $id): DynamicOptionRepositoryInterface
    {
        foreach ($this->getProductDynamicOptionCollection($id) as $entity) {
            $this->delete($entity);
        }

        return $this;
    }

    /**
     * Get DynamicOption Collection
     * @param int $productId
     * @param bool $useCache
     * @return DynamicOptionCollection
     */
    public function getProductDynamicOptionCollection(int $productId, bool $useCache = true): DynamicOptionCollection
    {
        if (!$useCache || !isset($this->collectionCache[$productId])) {
            /** @var DynamicOptionCollection $dynamicOptionCollection */
            $dynamicOptionCollection = $this->dynamicOptionCollectionFactory->create();
            $dynamicOptionCollection->addFieldToFilter(DynamicOptionInterface::PRODUCT_ID, $productId);
            $this->collectionCache[$productId] = $dynamicOptionCollection;
        }

        return $this->collectionCache[$productId];
    }

    /**
     * Get DynamicOption Ids
     *
     * @param int $productId
     * @param bool $useCache
     * @return \MageWorx\DynamicOptionsBase\Model\ResourceModel\DynamicOption\Collection
     */
    public function getProductDynamicOptionIds(int $productId, bool $useCache = true)
    {
        if (!$useCache || !isset($this->idsCache[$productId])) {
            /** @var DynamicOptionCollection $dynamicOptionCollection */
            $dynamicOptionCollection = $this->dynamicOptionCollectionFactory->create();
            $this->idsCache[$productId] = $dynamicOptionCollection->getProductDynamicOptionIds($productId);
        }

        return $this->idsCache[$productId];
    }
}
