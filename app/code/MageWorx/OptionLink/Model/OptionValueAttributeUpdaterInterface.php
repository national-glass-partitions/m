<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See https://www.mageworx.com/terms-and-conditions for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionLink\Model;

use Magento\Catalog\Model\Product;

interface OptionValueAttributeUpdaterInterface
{
    public function process(array $optionTypeIds, Product $product): bool;
}
