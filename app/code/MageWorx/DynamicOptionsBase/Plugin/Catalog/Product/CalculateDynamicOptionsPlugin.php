<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\DynamicOptionsBase\Plugin\Catalog\Product;

use Magento\Catalog\Api\Data\ProductCustomOptionInterface;
use MageWorx\DynamicOptionsBase\Api\DynamicOptionRepositoryInterface;
use MageWorx\DynamicOptionsBase\Model\CalculateDynamicOptionsPriceFactory;
use MageWorx\DynamicOptionsBase\Api\Data\DynamicOptionsConfigReaderInterface;

class CalculateDynamicOptionsPlugin
{
    private DynamicOptionRepositoryInterface $dynamicOptionRepository;
    private CalculateDynamicOptionsPriceFactory $calculateDynamicOptionsPriceFactory;
    private DynamicOptionsConfigReaderInterface $configReader;
    private array $cachedPrice = [];

    /**
     * CalculateDynamicOptionsPlugin constructor.
     *
     * @param DynamicOptionRepositoryInterface $dynamicOptionRepository
     * @param DynamicOptionsConfigReaderInterface $configReader
     * @param CalculateDynamicOptionsPriceFactory $calculateDynamicOptionsPriceFactory
     */
    public function __construct(
        DynamicOptionRepositoryInterface $dynamicOptionRepository,
        DynamicOptionsConfigReaderInterface $configReader,
        CalculateDynamicOptionsPriceFactory $calculateDynamicOptionsPriceFactory
    ) {
        $this->dynamicOptionRepository             = $dynamicOptionRepository;
        $this->configReader                        = $configReader;
        $this->calculateDynamicOptionsPriceFactory = $calculateDynamicOptionsPriceFactory;
    }

    /**
     * @param ProductCustomOptionInterface $option
     * @param int $price
     * @return float|null
     */
    public function afterGetPrice(ProductCustomOptionInterface $option, $price)
    {
        if (!$this->configReader->isEnabled()) {
            return $price;
        }

        $product = $option->getProduct();

        if (!$product) {
            return $price;
        }

        $customOptions = $product->getCustomOptions();
        $item          = null;
        if (isset($customOptions['info_buyRequest'])) {
            $buyRequest = $customOptions['info_buyRequest'];
            /** @var \Magento\Quote\Model\Quote\Item $item */
            $item = $buyRequest->getItem();
        }

        if ($item && $item->getItemId() && isset($this->cachedPrice[$item->getItemId()][$option->getOptionId()])) {
            return $this->cachedPrice[$item->getItemId()][$option->getOptionId()];
        }

        if (!isset($customOptions['option_' . $option->getOptionId()])) {
            return $price;
        }

        $dynamicOptionIds = $this->dynamicOptionRepository->getProductDynamicOptionIds((int)$product->getId());
        foreach ($dynamicOptionIds as $id) {
            if ($id == $option->getOptionId()) {
                $calculateDynamicOptionPrice = $this->calculateDynamicOptionsPriceFactory->create($product);

                $price = $option->getDefaultPrice() + $calculateDynamicOptionPrice->execute($product);
            }

            break; //add dynamic price only to first option
        }

        if ($item && $item->getItemId()) {
            $this->cachedPrice[$item->getItemId()][$option->getOptionId()] = $price;
        }

        return $price;
    }
}
