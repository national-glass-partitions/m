<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionBase\Plugin;

use MageWorx\OptionBase\Model\ValidationResolver;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionBase\Helper\System as BaseSystemHelper;
use Magento\Catalog\Model\Product\Option\Type\DefaultType;

class ValidateAddToCart
{
    protected BaseHelper $baseHelper;
    protected BaseSystemHelper $baseSystemHelper;
    protected ValidationResolver $validationResolver;

    /**
     * @param ValidationResolver $validationResolver
     * @param BaseHelper $baseHelper
     */
    public function __construct(
        ValidationResolver $validationResolver,
        BaseHelper $baseHelper,
        BaseSystemHelper $baseSystemHelper
    ) {
        $this->validationResolver = $validationResolver;
        $this->baseHelper         = $baseHelper;
        $this->baseSystemHelper   = $baseSystemHelper;
    }

    /**
     * Check custom conditions to allow validate options on add to cart action
     *
     * @param DefaultType $subject
     * @param array $values
     * @return array
     */
    public function beforeValidateUserValue(DefaultType $subject, $values)
    {
        $option = $subject->getOption();

        /* Using for MW Order Editing process
         *
         * Was needed in the old OM and APO updates for the issue 907
         * Not needed in the lates update (need to re-check)
         *
         * @TODO..
         */
//        if ($this->baseSystemHelper->isEditingByOrderEditor()) {
//            $product = $subject->getProduct();
//            if (!$product->getSkipCheckRequiredOption() && $product->getHasOptions()) {
//                $options = $product->getProductOptionsCollection();
//                foreach ($options as $option) {
//                    if ($option->getIsRequire()) {
//                        $customOption = $product->getCustomOption('option_' . $option->getId());
//                        if (!$customOption || strlen($customOption->getValue()) == 0) {
//                            $product->setSkipCheckRequiredOption(true);
//                        }
//                    }
//                }
//            }
//        }

        if (!$option->getIsRequire()) {
            return [$values];
        }

        if (!$this->validationResolver->getValidators()) {
            return [$values];
        }

        /* values gets from request, but in case like hyva request is empty */
        $values = !is_null($values) ? $values : [];

        /* @var $validatorItem \MageWorx\OptionBase\Api\ValidatorInterface */
        foreach ($this->validationResolver->getValidators() as $validatorItem) {

            // checkout_cart_configure - using for edit product configuration from checkout/cart
            if (!$validatorItem->canValidateAddToCart($subject, $values) &&
                $this->baseHelper->getFullActionName() != 'checkout_cart_configure'
            ) {
                $option->setIsRequire(false);

                return [$values];
            }
        }

        return [$values];
    }
}
