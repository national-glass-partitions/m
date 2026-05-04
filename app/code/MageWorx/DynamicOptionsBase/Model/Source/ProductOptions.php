<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\DynamicOptionsBase\Model\Source;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Framework\Registry;
use MageWorx\DynamicOptionsBase\Api\Data\DynamicOptionInterface;
use MageWorx\DynamicOptionsBase\Model\Source;
use Magento\Catalog\Api\ProductCustomOptionRepositoryInterface;

class ProductOptions extends Source
{
    protected LocatorInterface $locator;
    private ProductCustomOptionRepositoryInterface $customOptionRepository;

    /**
     * MeasurementUnits constructor.
     *
     * @param LocatorInterface $locator
     * @param ProductCustomOptionRepositoryInterface $customOptionRepository
     */
    public function __construct(
        LocatorInterface $locator,
        ProductCustomOptionRepositoryInterface $customOptionRepository
    ) {
        $this->locator                = $locator;
        $this->customOptionRepository = $customOptionRepository;
    }

    public function toOptionArray(): array
    {
        $optionsArray = [];
        $product      = $product = $this->locator->getProduct();
        if (!$product) {
            return $optionsArray;
        }

        $options = $this->customOptionRepository->getProductOptions($product);

        /** var ProductCustomOptionInterface $option */
        foreach ($options as $option) {
            if (array_search($option->getType(), DynamicOptionInterface::COMPATIBLE_TYPES) === false) {
                continue;
            }

            $title = $option->getTitle();
            //@todo add '(dependency title id)' if available
            $optionsArray[] = ['value' => $option->getOptionId(), 'label' => $title];
        }

        return $optionsArray;
    }
}
