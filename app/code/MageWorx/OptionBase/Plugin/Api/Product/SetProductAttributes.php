<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionBase\Plugin\Api\Product;

use Magento\Catalog\Api\Data\ProductExtension;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Event\ManagerInterface;
use MageWorx\OptionBase\Model\Product\Attributes as MageWorxProductAttributes;

/**
 * Class SetProductAttributes
 *
 * @package MageWorx\OptionBase\Plugin\Api\Product
 */
class SetProductAttributes
{
    protected ManagerInterface $eventManager;
    protected MageWorxProductAttributes $mageWorxProductAttributes;

    /**
     * @var Product[]
     */
    protected array $instances = [];

    /**
     * @var Product[]
     */
    protected array $instancesById = [];

    /**
     * SetProductAttributes constructor.
     *
     * @param ManagerInterface $eventManager
     * @param MageWorxProductAttributes $mageWorxProductAttributes
     */
    public function __construct(
        ManagerInterface $eventManager,
        MageWorxProductAttributes $mageWorxProductAttributes

    ) {
        $this->eventManager              = $eventManager;
        $this->mageWorxProductAttributes = $mageWorxProductAttributes;
    }

    /**
     * @param \Magento\Catalog\Model\ProductRepository $subject
     * @param $result
     * @param ProductInterface $product
     * @return ProductInterface
     */
    public function afterSave(\Magento\Catalog\Model\ProductRepository $subject, $result, ProductInterface $product)
    {
        if (!$product->getId()) {
            return $result;
        }
        /** @var ProductExtension $extensionAttributes */
        $extensionAttributes       = $product->getExtensionAttributes()->__toArray();
        $mageWorxProductAttributes = $this->mageWorxProductAttributes->getData();
        foreach ($mageWorxProductAttributes as $attribute) {
            $attributeName = $attribute->getName();
            if (isset($extensionAttributes[$attributeName])) {
                $result->setData($attributeName, $extensionAttributes[$attributeName]);
            }
        }
        $this->eventManager->dispatch(
            'mageworx_attributes_save_trigger',
            ['product' => $result, 'is_after_template' => false]
        );

        /* We need to reload the collection to display the correct response,
           because we are taking the collection from the cache */
        return $subject->get($product->getSku(), false, $product->getStoreId());
    }
}
