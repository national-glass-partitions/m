<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionLink\Plugin;

use \MageWorx\OptionLink\Helper\Attribute as HelperAttribute;
use \MageWorx\OptionLink\Model\ResourceModel\Product\Option\Value\FieldFactory;
use \MageWorx\OptionLink\Model\ResourceModel\Product\Option\Value\CollectionUpdater;
use \Magento\Framework\ObjectManagerInterface as ObjectManager;
use \Magento\Framework\Registry;

/**
 * Class BeforeLoad. Replace option fields data to product attributes selected in setting.
 * This class are used when Option Value Collection are loading.
 */
class BeforeLoad
{
    protected HelperAttribute $helperAttribute;
    protected FieldFactory $optionFieldFactory;
    protected CollectionUpdater $collectionUpdater;
    protected ?ObjectManager $objectManager = null;
    protected Registry $registry;

    /**
     * BeforeLoad constructor.
     *
     * @param HelperAttribute $helperAttribute
     * @param FieldFactory $optionFieldFactory
     * @param CollectionUpdater $collectionUpdater
     * @param ObjectManager $objectManager
     * @param Registry $registry
     */
    public function __construct(
        HelperAttribute $helperAttribute,
        FieldFactory $optionFieldFactory,
        CollectionUpdater $collectionUpdater,
        ObjectManager $objectManager,
        Registry $registry
    ) {
        $this->helperAttribute = $helperAttribute;
        $this->optionFieldFactory = $optionFieldFactory;
        $this->collectionUpdater = $collectionUpdater;
        $this->objectManager = $objectManager;
        $this->registry = $registry;
    }

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Product\Option\Value\Collection $collection
     * @param bool $printQuery
     * @param bool $logQuery
     * @return array
     */
    public function beforeLoad($collection, $printQuery = false, $logQuery = false)
    {
        $isOptionTemplateSave = $this->registry->registry('mageworx_optiontemplates_group_save');
        if ($isOptionTemplateSave) {
            return [$printQuery, $logQuery];
        }

        $sqlFrom = $collection->getSelect()->getPart('from');
        $sqlColumns = $collection->getSelect()->getPart('columns');
        $fields = $this->helperAttribute->getConvertedAttributesToFields();

        if (!$this->canUpdate($sqlFrom, $sqlColumns, $fields)) {
            return [$printQuery, $logQuery];
        }

        $this->collectionUpdater->joinProductTable($collection);

        $this->collectionUpdater->resetColumns($collection, $fields);

        $this->collectionUpdater->addOriginalColumns($collection, $fields);

        foreach ($fields as $field) {
            if ($this->collectionUpdater->canAddField($collection, $field)) {
                $this->optionFieldFactory->create($field)->addField($collection);
            }
        }

        return [$printQuery, $logQuery];
    }

    /**
     * Check if we can modify collection sql.
     *
     * @param $selectFields
     * @param $productAttributes
     * @return bool
     */
    protected function canUpdate($sqlFrom, $sqlColumns, $fields)
    {
        // Return False if sql was modified
        if (in_array(CollectionUpdater::KEY_TABLE_OPTIONLINK_PRODUCT, array_keys($sqlFrom))) {
            return false;
        }

        // Check count fields in "select" part of sql.
        // We modify sql which contains 2 or more fields.
        if (count($sqlColumns) <= 1) {
            return false;
        }

        // We modify sql when selected 1 or more product attributes.
        if (empty($fields)) {
            return false;
        }

        return true;
    }
}
