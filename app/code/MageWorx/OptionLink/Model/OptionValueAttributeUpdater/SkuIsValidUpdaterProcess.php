<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See https://www.mageworx.com/terms-and-conditions for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionLink\Model\OptionValueAttributeUpdater;

use MageWorx\OptionLink\Model\ResourceModel\Product\Option\Value\SkuIsValidUpdater as SkuIsValidUpdaterResourceModel;

class SkuIsValidUpdaterProcess
{
    protected SkuIsValidUpdaterResourceModel $skuIsValidUpdaterResourceModel;

    /**
     * SkuIsValidUpdaterProcess constructor.
     *
     * @param SkuIsValidUpdaterResourceModel $skuIsValidUpdaterResourceModel
     */
    public function __construct(
        SkuIsValidUpdaterResourceModel $skuIsValidUpdaterResourceModel
    ) {
        $this->skuIsValidUpdaterResourceModel = $skuIsValidUpdaterResourceModel;
    }

    /**
     * {@inheritdoc}
     */
    public function updateSkuIsValidAttributeDataOnSetup(bool $skuIsValid): void
    {
        $this->skuIsValidUpdaterResourceModel->updateOptionTypeIdByValidProductSkusOnSetup($skuIsValid);
    }

    /**
     * {@inheritdoc}
     */
    public function updateSkuIsValidAttributeData(bool $skuIsValid, string $sku): void
    {
        $this->skuIsValidUpdaterResourceModel->updateOptionTypeIdByValidProductSkus($skuIsValid, $sku);
    }
}
