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
use MageWorx\OptionBase\Model\Product\Option\Attributes as OptionAttributes;
use MageWorx\OptionTemplates\Model\ResourceModel\Group as GroupResourceModel;

class AroundDuplicateOption
{
    protected BaseEntityModel $baseEntityModel;
    protected OptionBaseHelper $helper;
    protected HttpRequest $request;
    protected Registry $registry;
    protected OptionAttributes $optionAttributes;
    protected GroupResourceModel $groupResourceModel;

    public function __construct(
        OptionAttributes $optionAttributes,
        BaseEntityModel $baseEntityModel,
        OptionBaseHelper $helper,
        HttpRequest $request,
        Registry $registry,
        GroupResourceModel $groupResourceModel
    ) {
        $this->optionAttributes   = $optionAttributes;
        $this->baseEntityModel    = $baseEntityModel;
        $this->helper             = $helper;
        $this->request            = $request;
        $this->registry           = $registry;
        $this->groupResourceModel = $groupResourceModel;
    }

    public function aroundDuplicate($subject, \Closure $proceed, $object, $oldProductId, $newProductId)
    {
        $connection = $subject->getConnection();

        $optionsCond = [];
        $optionsData = [];

        $oldIds = [];
        $mapId = [];

        // read and prepare original product options
        $select = $connection->select()->from(
            $subject->getTable('catalog_product_option')
        )->where(
            'product_id = ?',
            $oldProductId
        );

        $query = $connection->query($select);

        while ($row = $query->fetch()) {
            $oldIds[$row['option_id']] = $row['option_id'];

            $optionsData[$row['option_id']] = $row;
            $optionsData[$row['option_id']]['product_id'] = $newProductId;
            $optionsData[$row['option_id']]['option_id'] = null;
            unset($optionsData[$row['option_id']]['option_id']);
        }

        // insert options to duplicated product
        foreach ($optionsData as $oId => $data) {
            $connection->insert($subject->getMainTable(), $data);
            $optionsCond[$oId] = $connection->lastInsertId($subject->getMainTable());
        }

        // copy options prefs
        foreach ($optionsCond as $oldOptionId => $newOptionId) {
            // title
            $table = $subject->getTable('catalog_product_option_title');

            $select = $subject->getConnection()->select()->from(
                $table,
                [new \Zend_Db_Expr($newOptionId), 'store_id', 'title']
            )->where(
                'option_id = ?',
                $oldOptionId
            );

            $insertSelect = $connection->insertFromSelect(
                $select,
                $table,
                ['option_id', 'store_id', 'title'],
                \Magento\Framework\DB\Adapter\AdapterInterface::INSERT_ON_DUPLICATE
            );
            $connection->query($insertSelect);

            // price
            $table = $subject->getTable('catalog_product_option_price');

            $select = $connection->select()->from(
                $table,
                [new \Zend_Db_Expr($newOptionId), 'store_id', 'price', 'price_type']
            )->where(
                'option_id = ?',
                $oldOptionId
            );

            $insertSelect = $connection->insertFromSelect(
                $select,
                $table,
                ['option_id', 'store_id', 'price', 'price_type'],
                \Magento\Framework\DB\Adapter\AdapterInterface::INSERT_ON_DUPLICATE
            );
            $connection->query($insertSelect);

            // process mageworx option attributes
            $table = $subject->getTable('catalog_product_option');
            $select = $connection->select()->from(
                $table,
                ['option_id']
            )->where(
                'option_id = ?',
                $newOptionId
            );

            $oldId = $oldIds[$oldOptionId];
            $newId = $connection->fetchOne($select);

            foreach ($this->optionAttributes->getData() as $attribute) {
                $attribute->processDuplicate($newId, $oldId);
            }

            $object->getValueInstance()->duplicate($oldOptionId, $newOptionId);

            $mapId[$oldId] = $newId; // used in the DuplicateDependency plugin
        }

        // save old => new mageworx_id to Magento Register
        $mapOptionId = $this->registry->registry('mapOptionId');
        if (!isset($mapOptionId)) {
            $this->registry->register('mapOptionId', $mapId);
        }

        // relation template
        $this->groupResourceModel->duplicateTemplateRelations($newProductId, $oldProductId);

        return $object;
    }
}
