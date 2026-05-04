<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionLink\Observer;

use \MageWorx\OptionLink\Helper\Attribute as HelperAttribute;
use \MageWorx\OptionLink\Model\OptionValue as ModelOptionValue;
use \Magento\Store\Model\StoreManagerInterface as StoreManager;
use \Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\Event\Observer as EventObserver;

/**
 * Class UpdateOptionValuesBeforeGroupSave. Update option values data linked by SKU to original values data.
 * We save data linked by SKU when unlink option value only.
 * This observer we use when OptionTemplates saving.
 */
class UpdateOptionValuesBeforeGroupSave implements ObserverInterface
{
    protected HelperAttribute $helperAttribute;
    protected ModelOptionValue $modelOptionValue;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected StoreManager $storeManager;

    /**
     * UpdateOptionValuesBeforeGroupSave constructor.
     *
     * @param HelperAttribute $helperAttribute
     * @param ModelOptionValue $modelOptionValue
     * @param StoreManager $storeManager
     */
    public function __construct(
        HelperAttribute $helperAttribute,
        ModelOptionValue $modelOptionValue,
        StoreManager $storeManager
    ) {

        $this->helperAttribute = $helperAttribute;
        $this->modelOptionValue = $modelOptionValue;
        $this->storeManager = $storeManager;
    }

    /**
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(EventObserver $observer)
    {
        $data = $observer->getRequest()->getParam('mageworx_optiontemplates_group');

        if (!isset($data['options'])) {
            return $this;
        }

        $currentOptions = $data['options'];

        $originalValues = $this->getOriginalOptions($currentOptions);
        $fields = $this->helperAttribute->getConvertedAttributesToFields();

        foreach ($currentOptions as $opKey => $currentOption) {
            if (!isset($currentOption['values'])) {
                continue;
            }

            $currentValues = $this->modelOptionValue
                ->updateOptionValuesBeforeSave($currentOption['values'], $originalValues, $fields);

            $data['options'][$opKey]['values'] = $currentValues;
        }

        $observer->getRequest()->setParam('mageworx_optiontemplates_group', $data);

        return $this;
    }

    /**
     * Retrieve original option values data by options ids.
     *
     * @param array $options
     * @return array
     */
    protected function getOriginalOptions($options)
    {
        $optionIds = [];
        foreach ($options as $option) {
            $optionIds[] = $option['option_id'];
        }

        return $this->modelOptionValue->loadOriginalOptions($optionIds, false);
    }
}
