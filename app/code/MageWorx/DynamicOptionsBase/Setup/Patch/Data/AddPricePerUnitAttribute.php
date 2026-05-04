<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\DynamicOptionsBase\Setup\Patch\Data;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Eav\Setup\EavSetupFactory;

class AddPricePerUnitAttribute implements DataPatchInterface
{
    private ModuleDataSetupInterface $moduleDataSetup;
    private EavSetupFactory $eavSetupFactory;

    /**
     * AddPricePerUnitAttribute constructor.
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        /** @var \Magento\Eav\Setup\EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create();

        $attributeSetId = $eavSetup->getDefaultAttributeSetId(ProductAttributeInterface::ENTITY_TYPE_CODE);
        // Creating attribute group named
        $eavSetup->addAttributeGroup(
            ProductAttributeInterface::ENTITY_TYPE_CODE,
            $attributeSetId,
            "Mageworx Dynamic Options",
            40
        );

        $attributeFrom = $eavSetup->getAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'price_per_unit'
        );
        if (empty($attributeFrom)) {
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'price_per_unit',
                [
                    'group'                    => "mageworx-dynamic-options",
                    'type'                     => 'text',
                    'label'                    => 'Price Per Unit',
                    'input'                    => 'text',
                    'required'                 => false,
                    'sort_order'               => 20,
                    'global'                   => ScopedAttributeInterface::SCOPE_STORE,
                    'is_used_in_grid'          => false,
                    'is_visible_in_grid'       => false,
                    'is_filterable_in_grid'    => false,
                    'visible'                  => true,
                    'is_html_allowed_on_front' => false,
                    'visible_on_front'         => false,
                    'default'                  => '0',
                    'system'                   => 0,
                    'user_defined'             => false
                ]
            );
        } else {
            $eavSetup->addAttributeToGroup(
                \Magento\Catalog\Model\Product::ENTITY,
                $attributeSetId,
                "mageworx-dynamic-options",
                'price_per_unit',
                20
            );
        }

        $this->moduleDataSetup->endSetup();
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
}
