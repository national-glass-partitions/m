<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\GeoIP\Api\Data;

interface RegionRelationsInterface
{
    /**
     * Constants for DB tables
     */
    const RELATION_ID  = 'relation_id';
    const REGION_ID    = 'region_id';
    const COUNTRY_ID   = 'country_id';
    const COUNTRY_NAME = 'country_name';
    const STORE_IDS    = 'store_ids';

    /**
     * @param string $id
     * @return RegionRelationsInterface
     */
    public function setRegionId(string $id): RegionRelationsInterface;

    /**
     * @param string $id
     * @return RegionRelationsInterface
     */
    public function setCountryId(string $id): RegionRelationsInterface;

    /**
     * @param string $name
     * @return RegionRelationsInterface
     */
    public function setCountryName(string $name): RegionRelationsInterface;

    /**
     * @param array $ids
     * @return RegionRelationsInterface
     */
    public function setStoreIds(array $ids): RegionRelationsInterface;

    /**
     * @return string
     */
    public function getRelationId(): ?string;

    /**
     * @return string
     */
    public function getRegionId(): ?string;

    /**
     * @return string
     */
    public function getCountryId(): string;

    /**
     * @return string
     */
    public function getCountryName(): string;

    /**
     * @return array
     */
    public function getStoreIds(): array;
}
