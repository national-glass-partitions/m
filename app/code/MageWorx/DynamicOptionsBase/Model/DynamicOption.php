<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\DynamicOptionsBase\Model;

use MageWorx\DynamicOptionsBase\Api\Data\DynamicOptionInterface;
use MageWorx\DynamicOptionsBase\Model\Source\MeasurementUnits;

/**
 * Class DynamicOption
 */
class DynamicOption extends \Magento\Framework\Model\AbstractModel implements DynamicOptionInterface
{
    protected MeasurementUnits $measurementUnits;

    /**
     * Dynamic Option constructor.
     *
     * @param MeasurementUnits $measurementUnits
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        MeasurementUnits $measurementUnits,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->measurementUnits = $measurementUnits;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\MageWorx\DynamicOptionsBase\Model\ResourceModel\DynamicOption::class);
    }

    public function getProductId(): int
    {
        return (int)$this->getData(DynamicOptionInterface::PRODUCT_ID);
    }

    public function getOptionId(): int
    {
        return (int)$this->getData(DynamicOptionInterface::OPTION_ID);
    }

    /**
     * @return null|float
     */
    public function getStep(): float
    {
        return (float)$this->getData(DynamicOptionInterface::STEP);
    }

    /**
     * @return null|float
     */
    public function getMinValue(): float
    {
        return (float)$this->getData(DynamicOptionInterface::MIN_VALUE);
    }

    /**
     * @return null|float
     */
    public function getMaxValue(): float
    {
        return (float)$this->getData(DynamicOptionInterface::MAX_VALUE);
    }

    /**
     * @return null|string
     */
    public function getMeasurementUnit()
    {
        return $this->getData(DynamicOptionInterface::MEASUREMENT_UNIT);
    }

    public function setProductId(int $productId): DynamicOptionInterface
    {
        $this->setData(DynamicOptionInterface::PRODUCT_ID, $productId);

        return $this;
    }

    public function setOptionId(int $optionId): DynamicOptionInterface
    {
        $this->setData(DynamicOptionInterface::OPTION_ID, $optionId);

        return $this;
    }

    public function setStep(float $value): DynamicOptionInterface
    {
        $this->setData(DynamicOptionInterface::STEP, $value);

        return $this;
    }

    public function setMinValue(float $value): DynamicOptionInterface
    {
        $this->setData(DynamicOptionInterface::MIN_VALUE, $value);

        return $this;
    }

    public function setMaxValue(float $value): DynamicOptionInterface
    {
        $this->setData(DynamicOptionInterface::MAX_VALUE, $value);

        return $this;
    }

    /**
     * @param string $unit
     * @return DynamicOptionInterface
     * @throws \Magento\Framework\Exception\InputException
     */
    public function setMeasurementUnit(string $unit): DynamicOptionInterface
    {
        $availableUnits = array_flip($this->measurementUnits->toArray());
        if (array_search($unit, $availableUnits) === null) {
            throw new  \Magento\Framework\Exception\InputException(
                __(
                    'Unit doesn\'t exist: %1',
                    $unit
                )
            );
        }

        $this->setData(DynamicOptionInterface::MEASUREMENT_UNIT, $unit);

        return $this;
    }
}
