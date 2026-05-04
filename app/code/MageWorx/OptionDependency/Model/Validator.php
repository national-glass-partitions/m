<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionDependency\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductCustomOptionInterface;
use MageWorx\OptionBase\Api\ValidatorInterface;
use Magento\Catalog\Model\Product\Option\Type\DefaultType;
use MageWorx\OptionBase\Helper\Data as BaseHelper;

class Validator implements ValidatorInterface
{
    protected BaseHelper $baseHelper;
    protected Config $modelConfig;

    public function __construct(
        Config $modelConfig,
        BaseHelper $baseHelper
    ) {
        $this->modelConfig = $modelConfig;
        $this->baseHelper = $baseHelper;
    }

    /**
     * Run validation process for add to cart action
     *
     */
    public function canValidateAddToCart(DefaultType $subject, array $values): bool
    {
        return $this->process($subject->getProduct(), $subject->getOption(), $values);
    }

    /**
     * Run validation process for cart and checkout
     *
     */
    public function canValidateCartCheckout(ProductInterface $product, ProductCustomOptionInterface $option): bool
    {
        $buyRequest = $this->baseHelper->getInfoBuyRequest($product);
        if (empty($buyRequest)) {
            return true;
        }
        $values = $buyRequest['options'] ?? [];

        return $this->process($product, $option, $values);
    }

    /**
     * Check dependent option, if hidden - skip validation
     *
     */
    protected function process(ProductInterface $product, ProductCustomOptionInterface $option, array $values): bool
    {
        $productId = $this->baseHelper->isEnterprise() ?
            $product->getRowId() :
            $product->getId();

        $values = $option->getType() != ProductCustomOptionInterface::OPTION_TYPE_FILE ? $values : [];

        return $this->modelConfig->isNeedDependentOptionValidation(
            $option,
            $values,
            $product,
            (int)$productId
        );
    }
}
