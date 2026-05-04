<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionLink\Plugin;

use \MageWorx\OptionLink\Helper\Attribute as HelperAttribute;
use \MageWorx\OptionLink\Model\OptionValue as ModelOptionValue;
use \Magento\Store\Model\StoreManagerInterface;
use \Magento\Framework\Registry;

/**
 * Class BeforeSaveValues. Update option values data linked by SKU to original values data.
 * We save data linked by SKU when unlink option value only.
 * This plugin we use when Product saving.
 */
class BeforeSaveValues
{
    protected HelperAttribute $helperAttribute;
    protected ModelOptionValue $modelOptionValue;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;

    /**
     * Magento register
     *
     * @var \Magento\Framework\Registry
     */
    protected Registry $registry;

    /**
     * BeforeSaveValues constructor.
     *
     * @param HelperAttribute $helperAttribute
     * @param ModelOptionValue $modelOptionValue
     * @param StoreManagerInterface $storeManager
     * @param Registry $registry
     */
    public function __construct(
        HelperAttribute $helperAttribute,
        ModelOptionValue $modelOptionValue,
        StoreManagerInterface $storeManager,
        Registry $registry
    ) {
        $this->helperAttribute = $helperAttribute;
        $this->modelOptionValue = $modelOptionValue;
        $this->storeManager = $storeManager;
        $this->registry = $registry;
    }

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Product\Option\Value\Collection $collection
     * @return array
     */
    public function beforeSaveValues($collection)
    {
        // Update when Product saving.
        // For OptionTemplates saving we use UpdateOptionValuesBeforeGroupSave observer.
        if ($collection instanceof \MageWorx\OptionTemplates\Model\Group\Option\Value) {
            return null;
        }

        $originalValues = $this->getOriginalOptions($collection);

        if (empty($originalValues)) {
            return null;
        }

        $currentValues = $collection->getValues();
        $fields = $this->helperAttribute->getConvertedAttributesToFields();

        $currentValues = $this->modelOptionValue
            ->updateOptionValuesBeforeSave($currentValues, $originalValues, $fields);

        $collection->setValues($currentValues);

        return null;
    }

    /**
     * Retrieve original option values data by mageworx options ids.
     * This data stored in 'mageworx_optionlink_original_options' registry
     * created in BeforeDelete plugin.
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Option\Value\Collection $collection
     * @return array
     */
    protected function getOriginalOptions($collection)
    {
        $registry = $this->registry->registry('mageworx_optionlink_original_options');
        $originalOptions = $registry ? $registry : [];

        $ids = [];
        foreach ($collection->getValues() as $value) {
            if (isset($value['option_type_id'])) {
                $ids[] = $value['option_type_id'];
            }
        }

        $result = [];
        foreach ($originalOptions as $valueId => $value) {
            if (isset($value['option_type_id']) && in_array($value['option_type_id'], $ids)) {
                $result[$value['option_type_id']] = $value;
            }
        }

        return $result;
    }
}
