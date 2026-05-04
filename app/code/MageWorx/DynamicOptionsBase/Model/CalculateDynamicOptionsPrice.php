<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\DynamicOptionsBase\Model;

use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\StoreManagerInterface;
use MageWorx\DynamicOptionsBase\Api\CalculateDynamicOptionsPriceInterface;
use MageWorx\DynamicOptionsBase\Api\DynamicOptionRepositoryInterface;

/**
 * Class CalculateDynamicOptionsPrice
 */
class CalculateDynamicOptionsPrice implements CalculateDynamicOptionsPriceInterface
{
    private DynamicOptionRepositoryInterface $dynamicOptionRepository;
    private SerializerInterface $serializer;
    protected StoreManagerInterface $storeManager;

    /**
     * CalculateDynamicOptionsPrice constructor.
     *
     * @param StoreManagerInterface $storeManager
     * @param SerializerInterface $serializer
     * @param DynamicOptionRepositoryInterface $dynamicOptionRepository
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        SerializerInterface $serializer,
        DynamicOptionRepositoryInterface $dynamicOptionRepository
    ) {
        $this->storeManager            = $storeManager;
        $this->serializer              = $serializer;
        $this->dynamicOptionRepository = $dynamicOptionRepository;
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @return float|null
     */
    public function execute(\Magento\Catalog\Api\Data\ProductInterface $product): float
    {
        $options = $product->getCustomOptions();
        if (empty($options)) {
            return 0;
        }

        if (!isset($options['info_buyRequest'])) {
            return 0;
        }

        $buyRequest = $this->serializer->unserialize($options['info_buyRequest']->getValue());

        /** @var array $options */
        $options = $buyRequest['options'] ?? [];
        if (empty($options)) {
            return 0;
        }

        $pricePerUnit = $product->getResource()->getAttributeRawValue(
            $product->getId(),
            'price_per_unit',
            $this->getCurrentStoreId()
        );

        if (!$pricePerUnit) {
            return 0;
        }

        $dynamicOptions = $this->dynamicOptionRepository->getProductDynamicOptionCollection((int)$product->getId());

        $unit = 1;

        foreach ($dynamicOptions as $dynamicOption) {
            if (!isset($options[$dynamicOption->getOptionId()])) {
                return 0;
            }

            $unit *= $options[$dynamicOption->getOptionId()];
        }

        return $unit * $pricePerUnit;
    }

    /**
     * @return string
     */
    protected function getCurrentStoreId()
    {
        return (string)$this->storeManager->getStore()->getId();
    }
}
