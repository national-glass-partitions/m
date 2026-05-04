<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionFeatures\Plugin;

class SkipConfigurableOptionCalculation extends \Magento\Catalog\Model\Product\Type\Price
{
    /**
     * Skip option price calculation because we do it in another plugin:
     * @see \MageWorx\OptionFeatures\Plugin\AroundGetBasePrice
     *
     * @param \Magento\ConfigurableProduct\Model\Product\Type\Configurable\Price $subject
     * @param callable $proceed
     * @param \Magento\Catalog\Model\Product $product
     * @param null $qty
     * @return mixed
     */
    public function aroundGetFinalPrice($subject, $proceed, $qty, $product)
    {
        if ($qty === null && $product->getCalculatedFinalPrice() !== null) {
            return $product->getCalculatedFinalPrice();
        }
        if ($product->getCustomOption('simple_product') && $product->getCustomOption('simple_product')->getProduct()) {
            $finalPrice = parent::getFinalPrice($qty, $product->getCustomOption('simple_product')->getProduct());
        } else {
            $priceInfo = $product->getPriceInfo();
            $finalPrice = $priceInfo->getPrice('final_price')->getAmount()->getValue();
        }
        $finalPrice = max(0, $finalPrice);
        $product->setFinalPrice($finalPrice);

        return $finalPrice;
    }
}
