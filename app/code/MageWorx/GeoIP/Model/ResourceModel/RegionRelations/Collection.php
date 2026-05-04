<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\GeoIP\Model\ResourceModel\RegionRelations;

use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Store\Model\Store;

class Collection extends AbstractCollection
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \MageWorx\GeoIP\Model\RegionRelations::class,
            \MageWorx\GeoIP\Model\ResourceModel\RegionRelations::class
        );
        $this->_setIdFieldName($this->_idFieldName);
    }

    /**
     * @return Collection
     */
    protected function _afterLoad()
    {
        $this->_eventManager->dispatch(
            'mageworx_geoip_region_relation_after_load',
            [
                'collection' => $this,
                'connection' => $this->getConnection()
            ]
        );

        return parent::_afterLoad();
    }

    /**
     * @param string $attribute
     * @param string[]|null $condition
     * @return $this
     */
    public function addFieldToFilter($attribute, $condition = null)
    {
        if ($attribute == 'store_id') {
            $this->_eventManager->dispatch(
                'mageworx_geoip_region_relation_add_store_filter',
                [
                    'collection' => $this,
                    'condition' => $condition
                ]
            );
            return $this;
        }

        return parent::addFieldToFilter($attribute, $condition);
    }

}
