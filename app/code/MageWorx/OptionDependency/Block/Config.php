<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionDependency\Block;

use \Magento\Catalog\Model\Product\Option\Repository as OptionRepository;
use Magento\Framework\Profiler;
use \MageWorx\OptionDependency\Model\Config as ConfigModel;
use \Magento\Framework\Registry;
use \Magento\Framework\View\Element\Template\Context;
use MageWorx\OptionBase\Model\Entity\Base as MageworxBaseEntity;
use \MageWorx\OptionBase\Helper\Data as BaseHelper;
use \MageWorx\OptionBase\Helper\System as SystemHelper;
use MageWorx\OptionBase\Model\ResourceModel\Option as OptionModel;
use MageWorx\OptionDependency\Model\HiddenDependents as HiddenDependentsModel;

/**
 * Autocomplete class used to paste config data
 */
class Config extends \Magento\Framework\View\Element\Template
{
    protected BaseHelper $baseHelper;
    protected SystemHelper $systemHelper;
    protected ConfigModel $modelConfig;
    protected OptionModel $optionModel;
    protected Registry $registry;
    protected OptionRepository $productOptionsRepository;
    protected HiddenDependentsModel $hiddenDependentsModel;
    protected MageworxBaseEntity $mageworxBaseEntity;

    protected string $jsonData = '';

    public function __construct(
        ConfigModel $modelConfig,
        OptionModel $optionModel,
        Registry $registry,
        OptionRepository $repository,
        Context $context,
        BaseHelper $baseHelper,
        SystemHelper $systemHelper,
        HiddenDependentsModel $hiddenDependentsModel,
        MageworxBaseEntity $mageworxBaseEntity,
        array $data = []
    ) {
        $this->modelConfig              = $modelConfig;
        $this->optionModel              = $optionModel;
        $this->registry                 = $registry;
        $this->productOptionsRepository = $repository;
        $this->baseHelper               = $baseHelper;
        $this->systemHelper             = $systemHelper;
        $this->hiddenDependentsModel    = $hiddenDependentsModel;
        $this->mageworxBaseEntity       = $mageworxBaseEntity;
        parent::__construct($context, $data);
    }

    /**
     * Get config json data
     *
     * @return string JSON
     */
    public function getJsonData()
    {
        Profiler::start('APO1: Option Dependency::' . __METHOD__);
        if (!empty($this->jsonData)) {
            Profiler::stop('APO1: Option Dependency::' . __METHOD__);
            return $this->jsonData;
        }

        $optionValueMaps = $this->getValueOptionMaps();
        $data = [
            'isAdmin'              => $this->isAdminArea(),
            'optionToValueMap'     => $optionValueMaps['optionToValue'],
            'valueToOptionMap'     => $optionValueMaps['valueToOption'],
            'optionTypes'          => $this->getOptionTypes(),
            'optionRequiredConfig' => $this->getOptionsRequiredParam(),
            'selectedValues'       => $this->getPreselectedValues(),
            'hiddenOptions'        => $this->getHiddenOptions(),
            'hiddenValues'         => $this->getHiddenValues(),
            'dependencyRulesJson'  => $this->getDependencyRules()
        ];

        $this->jsonData = (string)$this->baseHelper->jsonEncode($data);

        Profiler::stop('APO1: Option Dependency::' . __METHOD__);
        return $this->jsonData;
    }

    /**
     * Get current product
     *
     * @return string
     */
    protected function getProduct()
    {
        return $this->registry->registry('product');
    }

    /**
     * Get option types ('option_id' => 'type') in json
     *
     * @return array
     */
    public function getOptionTypes()
    {
        return $this->optionModel->getOptionTypes($this->getProductId());
    }

    /**
     * Get option to values map from option's array
     *
     * @return array
     */
    public function getValueOptionMaps()
    {
        $options = $this->mageworxBaseEntity->getOptionsAsArray($this->registry->registry('product'));

        return $this->hiddenDependentsModel->getValueOptionMaps($options);
    }

    /**
     * Get preselected values from initial state
     *
     * @return array
     */
    protected function getPreselectedValues()
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $this->registry->registry('product');

        $hiddenDependents = $this->getHiddenDependents($product);

        if (empty($hiddenDependents['preselected_values'])) {
            return [];
        }
        return $hiddenDependents['preselected_values'];
    }

    /**
     * Get preselected values from initial state
     *
     * @return array
     */
    protected function getHiddenOptions()
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $this->registry->registry('product');

        $hiddenDependents = $this->getHiddenDependents($product);

        if (empty($hiddenDependents['hidden_options'])) {
            return [];
        }
        return $hiddenDependents['hidden_options'];
    }

    /**
     * Get preselected values from initial state
     *
     * @return array
     */
    protected function getHiddenValues()
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $this->registry->registry('product');

        $hiddenDependents = $this->getHiddenDependents($product);

        if (empty($hiddenDependents['hidden_values'])) {
            return [];
        }
        return $hiddenDependents['hidden_values'];
    }

    /**
     * Get dependency rules json
     *
     * @return array
     */
    protected function getDependencyRules()
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $this->registry->registry('product');

        return $product->getDependencyRules() ?: [];
    }

    /**
     * Get hidden dependents data considering source
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return array
     */
    protected function getHiddenDependents($product)
    {
        if ($this->systemHelper->isConfigureQuoteItemsAction()
            || $this->systemHelper->isCheckoutCartConfigureAction()
        ) {
            return $this->hiddenDependentsModel->getConfigureQuoteItemsHiddenDependents();
        } else {
            if (empty($product->getHiddenDependents())) {
                return [];
            }

            $hiddenDependentsJson = $product->getHiddenDependents();
            return $this->baseHelper->jsonDecode($hiddenDependentsJson);
        }
    }

    /**
     * Returns array with key -> mageworx option ID , value -> is option required
     * Used in the admin area during order creation to add a valid css classes when toggle option based on dependencies
     *
     * @return array
     */
    public function getOptionsRequiredParam()
    {
        $config = [];
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $this->registry->registry('product');
        /** @var \Magento\Catalog\Model\Product\Option[] $options */
        $options = $product->getOptions();
        foreach ($options as $option) {
            $config[$option->getId()]              = (bool)$option->getIsRequire();
            $config[$option->getData('option_id')] = (bool)$option->getIsRequire();
        }

        return $config;
    }

    /**
     * Get product options
     *
     * @return array
     */
    protected function getProductOptions()
    {
        $product = $this->registry->registry('product');

        return $product->getOptions();
    }

    /**
     * Get product id
     *
     * @return string
     */
    protected function getProductId()
    {
        $product = $this->registry->registry('product');

        return $this->baseHelper->isEnterprise() ? (string)$product->getRowId() : (string)$product->getId();
    }

    /**
     * Check Admin Area
     *
     * @return bool
     */
    public function isAdminArea()
    {
        return $this->systemHelper->isAdmin();
    }
}
