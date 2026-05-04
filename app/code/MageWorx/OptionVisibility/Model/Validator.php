<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionVisibility\Model;

use MageWorx\OptionBase\Api\ValidatorInterface;
use Magento\Catalog\Model\Product\Option\Type\DefaultType;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionBase\Helper\CustomerVisibility as VisibilityHelper;
use MageWorx\OptionVisibility\Helper\Data as Helper;
use Magento\Catalog\Api\Data\ProductCustomOptionInterface;
use Magento\Catalog\Api\Data\ProductInterface;

class Validator implements ValidatorInterface
{
    protected BaseHelper $baseHelper;
    protected VisibilityHelper $visibilityHelper;

    public function __construct(
        BaseHelper $baseHelper,
        VisibilityHelper $visibilityHelper
    ) {
        $this->baseHelper       = $baseHelper;
        $this->visibilityHelper = $visibilityHelper;
    }

    /**
     * Run validation process for add to cart action
     *
     */
    public function canValidateAddToCart(DefaultType $subject, array $values): bool
    {
        $option = $subject->getOption();
        return $this->process($option);
    }

    /**
     * Run validation process for cart and checkout
     *
     */
    public function canValidateCartCheckout(ProductInterface $product, ProductCustomOptionInterface $option): bool
    {
        return $this->process($option);
    }

    /**
     * Process validation
     */
    protected function process(ProductCustomOptionInterface $option): bool
    {
        if (!empty($option[Helper::KEY_DISABLED]) || !empty($option[Helper::KEY_DISABLED_BY_VALUES])) {
            return false;
        }

        if (!$this->baseHelper->isSelectableOption($option->getType())) {
            return true;
        }

        $values = $option->getValues();

        foreach ($values as $value) {
            if (empty($value[Helper::KEY_DISABLED])) {
                return true;
            }
        }

        return false;
    }
}
