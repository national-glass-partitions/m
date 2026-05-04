<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\DynamicOptionsBase\Plugin;

use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;

/**
 * Class ConfigPlugin
 */
class UpdatePriceScopeOnConfigSavingPlugin
{
    private AttributeRepositoryInterface $attributeRepository;
    protected \Magento\Framework\App\RequestInterface $request;
    protected bool $needUpdate = false;
    protected int $newScope;

    /**
     * UpdatePriceScopeOnConfigSavingPlugin constructor.
     *
     * @param AttributeRepositoryInterface $attributeRepository
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        AttributeRepositoryInterface $attributeRepository,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->request             = $request;
    }

    /**
     * @param \Magento\Config\Model\Config $subject
     * @return string[]
     */
    public function beforeSave(\Magento\Config\Model\Config $subject)
    {
        if ($this->request->getParam('section') !== 'catalog') {
            return [];
        }

        $groups = $this->request->getParam('groups');

        if (!isset($groups['price'])) {
            return [];
        }

        if (!isset($groups['price']['fields']['scope']['value'])) {
            return [];
        }

        $newScope = $groups['price']['fields']['scope']['value'] === \Magento\Catalog\Helper\Data::PRICE_SCOPE_GLOBAL ?
            ScopedAttributeInterface::SCOPE_GLOBAL : ScopedAttributeInterface::SCOPE_WEBSITE;

        $attribute = $this->attributeRepository->get(
            \Magento\Catalog\Model\Product::ENTITY,
            'price_per_unit'
        );
        $oldScope  = $attribute->getIsGlobal();

        if ($newScope == $oldScope) {
            return [];
        }

        $this->needUpdate = true;
        $this->newScope   = $newScope;

        return [];
    }

    /**
     * @param \Magento\Config\Model\Config $subject
     * @param \Magento\Config\Model\Config $result
     * @return \Magento\Config\Model\Config
     */
    public function afterSave(\Magento\Config\Model\Config $subject, $result)
    {
        if (!$this->needUpdate) {
            return $result;
        }

        $attribute = $this->attributeRepository->get(
            \Magento\Catalog\Model\Product::ENTITY,
            'price_per_unit'
        );

        $attribute->setIsGlobal($this->newScope);
        $attribute->save();

        return $result;
    }
}
