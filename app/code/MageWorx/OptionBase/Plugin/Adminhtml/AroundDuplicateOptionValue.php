<?php

/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionBase\Plugin\Adminhtml;

use MageWorx\OptionBase\Model\Entity\Base as BaseEntityModel;
use MageWorx\OptionBase\Helper\Data as OptionBaseHelper;
use \Magento\Framework\App\Request\Http as HttpRequest;
use \Magento\Framework\Registry;
use MageWorx\OptionBase\Model\Product\Option\Value\Attributes as OptionValueAttributes;

class AroundDuplicateOptionValue
{
    protected BaseEntityModel $baseEntityModel;
    protected OptionBaseHelper $helper;
    protected HttpRequest $request;
    protected Registry $registry;
    protected OptionValueAttributes $optionValueAttributes;

    public function __construct(
        OptionValueAttributes $optionValueAttributes,
        BaseEntityModel $baseEntityModel,
        OptionBaseHelper $helper,
        HttpRequest $request,
        Registry $registry
    ) {
        $this->optionValueAttributes = $optionValueAttributes;
        $this->baseEntityModel = $baseEntityModel;
        $this->helper = $helper;
        $this->request = $request;
        $this->registry = $registry;
    }

    public function aroundDuplicate($subject, \Closure $proceed, $object, $oldOptionId, $newOptionId)
    {
        $connection = $subject->getConnection();
        $select = $connection->select()->from($subject->getMainTable())->where('option_id = ?', $oldOptionId);
        $valueData = $connection->fetchAll($select);

        $valueCond = [];
        $oldIds = [];
        $mapId = [];

        foreach ($valueData as $data) {
            $oldIds[$data['option_type_id']] = $data['option_type_id'];
            $optionTypeId = $data[$subject->getIdFieldName()];
            unset($data[$subject->getIdFieldName()]);
            $data['option_id'] = $newOptionId;
            $data['option_type_id'] = null;

            $connection->insert($subject->getMainTable(), $data);
            $valueCond[$optionTypeId] = $connection->lastInsertId($subject->getMainTable());
        }

        unset($valueData);

        foreach ($valueCond as $oldTypeId => $newTypeId) {
            // price
            $priceTable = $subject->getTable('catalog_product_option_type_price');
            $columns = [new \Zend_Db_Expr($newTypeId), 'store_id', 'price', 'price_type'];

            $select = $connection->select()->from(
                $priceTable,
                []
            )->where(
                'option_type_id = ?',
                $oldTypeId
            )->columns(
                $columns
            );
            $insertSelect = $connection->insertFromSelect(
                $select,
                $priceTable,
                ['option_type_id', 'store_id', 'price', 'price_type']
            );
            $connection->query($insertSelect);

            // title
            $titleTable = $subject->getTable('catalog_product_option_type_title');
            $columns = [new \Zend_Db_Expr($newTypeId), 'store_id', 'title'];

            $select = $subject->getConnection()->select()->from(
                $titleTable,
                []
            )->where(
                'option_type_id = ?',
                $oldTypeId
            )->columns(
                $columns
            );
            $insertSelect = $connection->insertFromSelect(
                $select,
                $titleTable,
                ['option_type_id', 'store_id', 'title']
            );
            $connection->query($insertSelect);

            // process mageworx option value attributes
            $table = $subject->getTable('catalog_product_option_type_value');
            $select = $connection->select()->from(
                $table,
                ['option_type_id']
            )->where(
                'option_type_id = ?',
                $newTypeId
            );

            $oldId = $oldIds[$oldTypeId];
            $newId = $connection->fetchOne($select);

            foreach ($this->optionValueAttributes->getData() as $attribute) {
                $attribute->processDuplicate($newId, $oldId);
            }

            $mapId[$oldId] = $newId; // used in the DuplicateDependency plugin
        }

        // save old => new mageworx_id to Magento Register
        $mapOptionTypeId = $this->registry->registry('mapOptionTypeId');
        if (isset($mapOptionTypeId)) {
            $this->registry->unregister('mapOptionTypeId');
            $mapId += $mapOptionTypeId;
        }
        $this->registry->register('mapOptionTypeId', $mapId);

        return $object;
    }
}
