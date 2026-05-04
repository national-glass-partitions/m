<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\DynamicOptionsBase\Api\Data;

/**
 * Dynamic Options interface
 */
interface DynamicOptionInterface
{
    const PRODUCT_ID            = 'product_id';
    const OPTION_ID             = 'option_id';
    const STEP                  = 'step';
    const MIN_VALUE             = 'min_value';
    const MAX_VALUE             = 'max_value';
    const PRICE_PER_UNIT        = 'price_per_unit';
    const MEASUREMENT_UNIT      = 'measurement_unit';
    const COMPATIBLE_TYPES      = [
        \Magento\Catalog\Api\Data\ProductCustomOptionInterface::OPTION_TYPE_FIELD,
        //\Magento\Catalog\Api\Data\ProductCustomOptionInterface::OPTION_TYPE_DROP_DOWN
    ];


    public function getProductId(): int;

    public function getOptionId(): int;

    /**
     * @return null|float
     */
    public function getStep(): float;

    /**
     * @return null|float
     */
    public function getMinValue(): float;

    /**
     * @return null|float
     */
    public function getMaxValue(): float;

    /**
     * @return null|string
     */
    public function getMeasurementUnit();

    public function setProductId(int $productId): \MageWorx\DynamicOptionsBase\Api\Data\DynamicOptionInterface;

    /**
     * @param int $optionId
     * @return \MageWorx\DynamicOptionsBase\Api\Data\DynamicOptionInterface
     */
    public function setOptionId(int $optionId);

    public function setStep(float $value): \MageWorx\DynamicOptionsBase\Api\Data\DynamicOptionInterface;

    public function setMinValue(float $value): \MageWorx\DynamicOptionsBase\Api\Data\DynamicOptionInterface;

    public function setMaxValue(float $value): \MageWorx\DynamicOptionsBase\Api\Data\DynamicOptionInterface;

    public function setMeasurementUnit(string $unit): \MageWorx\DynamicOptionsBase\Api\Data\DynamicOptionInterface;
}
