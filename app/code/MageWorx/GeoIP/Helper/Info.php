<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\GeoIP\Helper;

use Magento\Framework\App\Helper\Context;
use MageWorx\GeoIP\Api\RegionRelationsRepositoryInterface;

/**
 * GeoIP Info helper
 */
class Info extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var RegionRelationsRepositoryInterface
     */
    protected $regionRelationsRepository;

    /**
     * Info constructor.
     *
     * @param RegionRelationsRepositoryInterface $regionRelationsRepository
     * @param Context $context
     *
     */
    public function __construct(
        RegionRelationsRepositoryInterface $regionRelationsRepository,
        Context $context
    ) {
        $this->regionRelationsRepository = $regionRelationsRepository;
        parent::__construct($context);
    }

    /**
     *
     * To avoid inconsistency between Magento and Maxmind region names.
     * 01-11-2016 - http://geolite.maxmind.com/download/geoip/database/GeoLite2-City-CSV.zip
     *
     * @return array
     */
    public function getMaxmindData()
    {
        $data = [];

        foreach ($this->regionRelationsRepository->getList() as $regionRelation)
        {
            if (!isset($data[$regionRelation->getCountryId()])) {
                $data[$regionRelation->getCountryId()] = [
                    'value' => $regionRelation->getCountryId(),
                    'label' => $regionRelation->getCountryName(),
                    'regions' => []
                ];
            }
            if ($regionRelation->getRegionId()) {
                $data[$regionRelation->getCountryId()]['regions'][$regionRelation->getRegionId()] = $regionRelation->getRegionId();
            }
        }

        return $data;
    }
}
