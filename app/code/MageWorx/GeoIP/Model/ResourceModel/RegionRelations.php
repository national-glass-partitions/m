<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\GeoIP\Model\ResourceModel;

use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Store Relations resource model.
 *
 * Get and return info on stores relations.
 */
class RegionRelations extends AbstractDb
{
    /** @var EventManagerInterface */
    protected $eventManager;

    /**
     * RegionRelations constructor.
     *
     * @param EventManagerInterface $eventManager
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param null $connectionName
     */
    public function __construct(
        EventManagerInterface $eventManager,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $connectionName = null
    ) {
        $this->eventManager = $eventManager;
        parent::__construct($context, $connectionName);
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('maxmind_country_regions', 'relation_id');
    }

    /**
     * @param string $code
     * @return string
     */
    public function getCountryNameByCode(string $code): string
    {
        $connection = $this->getConnection();
        $select     = $connection->select()->from(
            $this->getMainTable(),
            'country_name'
        )->where('country_id = ?', $code);

        return $connection->fetchOne($select);
    }

    /**
     * @param \Magento\Framework\DataObject $object
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _beforeSave(\Magento\Framework\Model\AbstractModel $object)
    {
        $this->eventManager->dispatch(
            'mageworx_geoip_region_relation_before_save',
            [
                'object' => $object,
                'resource' => $this
            ]
        );

        return parent::_beforeSave($object);
    }
}
