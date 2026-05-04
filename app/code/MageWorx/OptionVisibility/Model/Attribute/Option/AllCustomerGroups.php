<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionVisibility\Model\Attribute\Option;

use Magento\Framework\DataObjectFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Serialize\Serializer\Json as Serializer;
use MageWorx\OptionVisibility\Helper\Data as Helper;
use MageWorx\OptionBase\Helper\System as SystemHelper;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionVisibility\Model\OptionCustomerGroup as CustomerGroupModel;
use MageWorx\OptionBase\Model\Product\Option\AbstractAttribute;

class AllCustomerGroups extends AbstractAttribute
{
    const KEY_ALL_CUSTOMER_GROUP = 'is_all_groups';

    protected Helper $helper;
    protected SystemHelper $systemHelper;
    /**
     * @var \MageWorx\OptionBase\Model\Entity\Group|\MageWorx\OptionBase\Model\Entity\Product
     */
    protected $entity;
    protected CustomerGroupModel $customerGroupModel;
    protected Serializer $serializer;

    /**
     * @param ResourceConnection $resource
     * @param Helper $helper
     * @param BaseHelper $baseHelper
     * @param SystemHelper $systemHelper
     * @param CustomerGroupModel $customerGroupModel
     * @param DataObjectFactory $dataObjectFactory
     * @param Serializer $serializer
     */
    public function __construct(
        ResourceConnection $resource,
        Helper $helper,
        CustomerGroupModel $customerGroupModel,
        BaseHelper $baseHelper,
        SystemHelper $systemHelper,
        DataObjectFactory $dataObjectFactory,
        Serializer $serializer
    ) {
        $this->helper             = $helper;
        $this->systemHelper       = $systemHelper;
        $this->customerGroupModel = $customerGroupModel;
        $this->serializer         = $serializer;
        parent::__construct($resource, $baseHelper, $dataObjectFactory);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return self::KEY_ALL_CUSTOMER_GROUP;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function hasOwnTable()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $type
     * @return string
     */
    public function getTableName($type = '')
    {
        return '';
    }

    /**
     * {@inheritdoc}
     *
     * @param \MageWorx\OptionBase\Model\Entity\Group|\MageWorx\OptionBase\Model\Entity\Product $entity
     * @param array $options
     * @return array
     */
    public function collectData($entity, array $options)
    {
        return [];
    }

    /**
     * Delete old option value
     *
     * @param array $data
     * @return void
     */
    public function deleteOldData(array $data)
    {
        return;
    }

    /**
     * {@inheritdoc}
     *
     * @param \Magento\Catalog\Model\Product\Option|\Magento\Catalog\Model\Product\Option\Value $object
     * @return array
     */
    public function prepareDataForFrontend($object)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     *
     * @param \Magento\Catalog\Model\Product\Option|\Magento\Catalog\Model\Product\Option\Value|array $data
     * @return string
     */
    public function prepareDataBeforeSave($data): string
    {
        if (is_object($data)) {
            $jsonCustomerGroup = $data->getData('customer_group');
        } elseif (is_array($data) && isset($data[$this->getName()])) {
            $jsonCustomerGroup = $data[$this->getName()];
        } else {
            return '';
        }

        $decodedJsonData = $jsonCustomerGroup ? $this->serializer->unserialize($jsonCustomerGroup) : null;

        if (empty($decodedJsonData) || !is_array($decodedJsonData)) {
            return '1';
        }

        foreach ($decodedJsonData as $key => $value) {
            if ($this->baseHelper->isAllCustomerGroupId((string)$value['customer_group_id'])) {
                return '1';
            }
        }

        return '0';
    }
}
