<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\GeoIP\Api;

use MageWorx\GeoIP\Api\Data\RegionRelationsInterface;
use MageWorx\GeoIP\Model\ResourceModel\RegionRelations\Collection;

interface RegionRelationsRepositoryInterface
{
    /**
     * @return RegionRelationsInterface
     */
    public function getEmptyEntity(): RegionRelationsInterface;

    /**
     * @param RegionRelationsInterface $regionRelation
     *
     * @return RegionRelationsInterface
     */
    public function save(RegionRelationsInterface $regionRelation): RegionRelationsInterface;

    /**
     * Get list relation
     *
     * @return Collection
     */
    public function getList() : Collection;

    /**
     * @param string $code
     * @return string
     */
    public function getCountryNameByCode(string $code): string;
}
