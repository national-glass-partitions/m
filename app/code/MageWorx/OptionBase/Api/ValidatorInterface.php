<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionBase\Api;

use Magento\Catalog\Api\Data\ProductCustomOptionInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Option\Type\DefaultType;

interface ValidatorInterface
{
    /**
     * Validation for option on add to cart action
     *
     * @param DefaultType $subject
     * @param array $values
     * @return bool
     */
    public function canValidateAddToCart(DefaultType $subject, array $values): bool;

    /**
     * Validation for option on cart and checkout
     *
     * @param ProductInterface $product
     * @param ProductCustomOptionInterface $option
     * @return bool
     */
    public function canValidateCartCheckout(ProductInterface $product, ProductCustomOptionInterface $option): bool;
}
