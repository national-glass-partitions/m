<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionLink\Plugin;

use \MageWorx\OptionLink\Model\OptionValue as ModelOptionValue;
use \Magento\Framework\Registry;
use \Magento\Store\Model\StoreManagerInterface;

/**
 * Class BeforeDelete. Grab original options data and save it to register before option delete.
 * After this BeforeSaveValues plugin grab options data from registry.
 * This plugin we use when Product saving.
 */
class BeforeDelete
{
    protected ModelOptionValue $modelOptionValue;

    /**
     * Magento register
     *
     * @var \Magento\Framework\Registry
     */
    protected Registry $registry;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;

    /**
     * BeforeDelete constructor.
     *
     * @param ModelOptionValue $modelOptionValue
     * @param Registry $registry
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ModelOptionValue $modelOptionValue,
        Registry $registry,
        StoreManagerInterface $storeManager
    ) {
        $this->modelOptionValue = $modelOptionValue;
        $this->registry = $registry;
        $this->storeManager = $storeManager;
    }

    /**
     * @param $subject
     * @param \Magento\Catalog\Api\Data\ProductCustomOptionInterface $option
     * @return array
     */
    public function beforeDelete($subject, \Magento\Catalog\Api\Data\ProductCustomOptionInterface $option)
    {
        $originalOptions = $this->modelOptionValue->loadOriginalOptions($option->getOptionId(), true);

        $registry = $this->registry->registry('mageworx_optionlink_original_options');
        $registryOptions = $registry ? $registry : [] ;

        $registryOptions += $originalOptions;

        $this->registry->unregister('mageworx_optionlink_original_options');
        $this->registry->register('mageworx_optionlink_original_options', $registryOptions);

        return [$option];
    }
}
