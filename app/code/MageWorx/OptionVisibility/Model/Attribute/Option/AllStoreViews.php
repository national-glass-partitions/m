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
use MageWorx\OptionVisibility\Model\OptionStoreView as StoreViewModel;
use MageWorx\OptionBase\Model\Product\Option\AbstractAttribute;

class AllStoreViews extends AbstractAttribute
{
    const KEY_ALL_STORE_VIEW = 'is_all_websites';

    protected Helper $helper;
    protected SystemHelper $systemHelper;
    /**
     * @var \MageWorx\OptionBase\Model\Entity\Group|\MageWorx\OptionBase\Model\Entity\Product
     */
    protected $entity;
    protected StoreViewModel $storeViewModel;
    protected Serializer $serializer;

    /**
     * @param ResourceConnection $resource
     * @param Helper $helper
     * @param SystemHelper $systemHelper
     * @param BaseHelper $baseHelper
     * @param StoreViewModel $storeViewModel
     * @param DataObjectFactory $dataObjectFactory
     * @param Serializer $serializer
     */
    public function __construct(
        ResourceConnection $resource,
        Helper $helper,
        StoreViewModel $storeViewModel,
        BaseHelper $baseHelper,
        DataObjectFactory $dataObjectFactory,
        SystemHelper $systemHelper,
        Serializer $serializer
    ) {
        $this->helper         = $helper;
        $this->systemHelper   = $systemHelper;
        $this->storeViewModel = $storeViewModel;
        $this->serializer     = $serializer;
        parent::__construct($resource, $baseHelper, $dataObjectFactory);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return self::KEY_ALL_STORE_VIEW;
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
            $jsonCustomerStoreView = $data->getData('store_view');
        } elseif (is_array($data) && isset($data[$this->getName()])) {
            return $data[$this->getName()];
        } else {
            return '';
        }

        $decodedJsonData = $jsonCustomerStoreView ? $this->serializer->unserialize($jsonCustomerStoreView) : null;

        if (empty($decodedJsonData) || !is_array($decodedJsonData)) {
            return '1';
        }

        foreach ($decodedJsonData as $key => $value) {
            if ($value['customer_store_id'] == 0) {
                return '1';
            }
        }

        return '0';
    }
}
