<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\GeoIP\Model;

use MageWorx\GeoIP\Api\Data\RegionRelationsInterface;

/**
 * Region Relations model.
 *
 * Get and return info on stores relations.
 */
class RegionRelations extends \Magento\Framework\Model\AbstractModel implements RegionRelationsInterface
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('MageWorx\GeoIP\Model\ResourceModel\RegionRelations');
    }

    /**
     * @param string $id
     * @return RegionRelationsInterface
     */
    public function setRegionId(string $id) : RegionRelationsInterface
    {
        $this->setData(RegionRelationsInterface::REGION_ID, $id);

        return $this;
    }

    /**
     * @param string $id
     * @return RegionRelationsInterface
     */
    public function setCountryId(string $id) : RegionRelationsInterface
    {
        $this->setData(RegionRelationsInterface::COUNTRY_ID, $id);

        return $this;
    }

    /**
     * @param string $name
     * @return RegionRelationsInterface
     */
    public function setCountryName(string $name): RegionRelationsInterface
    {
        $this->setData(RegionRelationsInterface::COUNTRY_NAME, $name);

        return $this;
    }

    /**
     * @param int $id
     * @return RegionRelationsInterface
     */
    public function addStoreId(int $id): RegionRelationsInterface
    {
        $storeIds = $this->getData(RegionRelationsInterface::STORE_IDS);
        if (is_array($storeIds)) {
            $storeIds[] = $id;
            $this->setData(RegionRelationsInterface::STORE_IDS, array_unique($storeIds));
        } else {
            $this->setData(RegionRelationsInterface::STORE_IDS, [$id]);
        }

        return $this;
    }

    /**
     * @param array $ids
     * @return $this|RegionRelationsInterface
     */
    public function setStoreIds(array $ids): RegionRelationsInterface
    {
        $this->setData(RegionRelationsInterface::STORE_IDS, $ids);

        return $this;
    }

    /**
     * @return string
     */
    public function getRelationId(): ?string
    {
        return $this->getData(RegionRelationsInterface::RELATION_ID);
    }


    /**
     * @return string
     */
    public function getRegionId() : ?string
    {
        return $this->getData(RegionRelationsInterface::REGION_ID);
    }

    /**
     * @return string
     */
    public function getCountryId() : string
    {
        return $this->getData(RegionRelationsInterface::COUNTRY_ID);
    }

    /**
     * @return string
     */
    public function getCountryName(): string
    {
        return $this->getData(RegionRelationsInterface::COUNTRY_NAME);
    }

    /**
     * @return array
     */
    public function getStoreIds(): array
    {
        $storeIds = $this->getData(RegionRelationsInterface::STORE_IDS);
        if (!is_array($storeIds)) {
            $storeIds = [];
        }

        return $storeIds;
    }
}
