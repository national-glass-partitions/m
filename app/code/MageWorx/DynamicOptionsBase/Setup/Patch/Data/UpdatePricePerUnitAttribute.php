<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\DynamicOptionsBase\Setup\Patch\Data;

use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Catalog\Setup\CategorySetupFactory;

class UpdatePricePerUnitAttribute implements DataPatchInterface, PatchVersionInterface
{
    /**
     * Category setup factory
     *
     * @var CategorySetupFactory
     */
    private CategorySetupFactory $categorySetupFactory;

    protected \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig;
    private ModuleDataSetupInterface $moduleDataSetup;

    /**
     * UpdatePricePerUnitAttribute constructor.
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CategorySetupFactory $categorySetupFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CategorySetupFactory $categorySetupFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->moduleDataSetup      = $moduleDataSetup;
        $this->categorySetupFactory = $categorySetupFactory;
        $this->scopeConfig          = $scopeConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $catalogSetup = $this->categorySetupFactory->create(['setup' => $this->moduleDataSetup]);
        $catalogSetup->updateAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'price_per_unit',
            'is_global',
            $this->isPriceGlobal() ?
                ScopedAttributeInterface::SCOPE_GLOBAL : ScopedAttributeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * Is Global Price
     *
     * @return bool
     */
    public function isPriceGlobal()
    {
        return $this->getPriceScope() == \Magento\Catalog\Helper\Data::PRICE_SCOPE_GLOBAL;
    }

    /**
     * Retrieve Catalog Price Scope
     *
     * @return int|null
     */
    public function getPriceScope()
    {
        $priceScope = $this->scopeConfig->getValue(
            \Magento\Catalog\Helper\Data::XML_PATH_PRICE_SCOPE,
            ScopeInterface::SCOPE_STORE
        );

        return isset($priceScope) ? (int)$priceScope : null;
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getVersion()
    {
        return '1.0.1';
    }
}
