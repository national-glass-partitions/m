<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionBase\Plugin;

use Magento\Tax\Pricing\Render\Adjustment;

class AroundGetDataPriceType
{
    /**
     * Temporary fix magento bug (Deprecated Functionality: ucfirst(): Passing null to parameter #1 ($string))
     *
     * @return string
     */
    public function aroundGetDataPriceType(Adjustment $subject, \Closure $proceed): string
    {
        if (is_null($subject->getAmountRender()->getPriceType())) {
            return '';
        }

        return $subject->getAmountRender()->getPriceType() === 'finalPrice'
            ? 'basePrice'
            : 'base' . ucfirst($subject->getAmountRender()->getPriceType());
    }

}
