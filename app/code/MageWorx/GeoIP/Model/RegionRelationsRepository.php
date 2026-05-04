<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\GeoIP\Model;

use MageWorx\GeoIP\Api\RegionRelationsRepositoryInterface;
use MageWorx\GeoIP\Api\Data\RegionRelationsInterface;
use MageWorx\GeoIP\Model\RegionRelationsFactory;
use MageWorx\GeoIP\Model\ResourceModel\RegionRelations as RegionRelationsResource;
use MageWorx\GeoIP\Model\ResourceModel\RegionRelations\CollectionFactory;
use MageWorx\GeoIP\Model\ResourceModel\RegionRelations\Collection;

/**
 * RegionRelationsRepository model.
 *
 * Get and return info on stores relations.
 */
class RegionRelationsRepository implements RegionRelationsRepositoryInterface
{
    /**
     * @var RegionRelationsFactory
     */
    private $regionRelationsFactory;

    /**
     * @var RegionRelationsResource
     */
    protected $resource;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * RegionRelationsRepository constructor.
     *
     * @param CollectionFactory $collectionFactory
     * @param \MageWorx\GeoIP\Model\RegionRelationsFactory $regionRelationsFactory
     * @param RegionRelationsResource $resource
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        RegionRelationsFactory $regionRelationsFactory,
        RegionRelationsResource $resource
    ) {
        $this->collectionFactory      = $collectionFactory;
        $this->regionRelationsFactory = $regionRelationsFactory;
        $this->resource               = $resource;
    }

    /**
     * @return RegionRelationsInterface
     */
    public function getEmptyEntity(): RegionRelationsInterface
    {
        /** @var RegionRelationsInterface $regionRelation */
        $regionRelation = $this->regionRelationsFactory->create();

        return $regionRelation;
    }

    /**
     * Save region relation.
     *
     * @param RegionRelationsInterface $regionRelation
     *
     * @return RegionRelationsInterface
     *
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(RegionRelationsInterface $regionRelation): RegionRelationsInterface
    {
        try {
            $this->resource->save($regionRelation);
        } catch (\Exception $exception) {
            throw new  \Magento\Framework\Exception\CouldNotSaveException(
                __(
                    'Could not save the region relation: %1',
                    $exception->getMessage()
                )
            );
        }

        return $regionRelation;
    }

    /**
     * Get list Relation
     *
     * @return Collection
     */
    public function getList(): Collection
    {
        /** @var Collection $locationCollection */
        $locationCollection = $this->collectionFactory->create();

        return $locationCollection;
    }

    /**
     * @param string $code
     * @return string
     */
    public function getCountryNameByCode(string $code): string
    {
        return $this->resource->getCountryNameByCode($code);
    }
}
