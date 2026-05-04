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
class CalculateDynamicOptionsPriceFactory
{
    /**
     * Object Manager instance
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager = null;

    /**
     * Instance name to create
     *
     * @var string|array
     */
    protected $map;

    /**
     * Factory constructor
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param array $map
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        array $map = []
    ) {
        $this->objectManager = $objectManager;
        $this->map           = $map;
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param array $arguments
     * @return CalculateDynamicOptionsPriceInterface|mixed
     */
    public function create(\Magento\Catalog\Api\Data\ProductInterface $product, array $arguments = [])
    {
        $formulaType = $product->getMageWorxDynamicOptionFormulaType() ?? 'default';

        if (isset($this->map[$formulaType])) {
            $instance = $this->objectManager->create($this->map[$formulaType], $arguments);
        } else {
            $instance = $this->objectManager->create(
                \MageWorx\DynamicOptionsBase\Model\CalculateDynamicOptionsPrice::class,
                $arguments
            );
        }

        if (!$instance instanceof CalculateDynamicOptionsPriceInterface) {
            throw new \UnexpectedValueException(
                'Class ' . get_class($instance) .
                ' should be an instance of MageWorx\DynamicOptionsBase\Api\CalculateDynamicOptionsPriceInterface'
            );
        }

        return $instance;
    }
}

