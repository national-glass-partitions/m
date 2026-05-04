<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionLink\Model\Attribute\OptionValue;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObjectFactory;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionLink\Helper\Data as Helper;
use MageWorx\OptionBase\Model\Product\Option\AbstractAttribute;
use MageWorx\OptionLink\Model\OptionValueSkuVlidator as SkuValidator;

class SkuIsValid extends AbstractAttribute
{
    protected SkuValidator $skuValidator;

    /**
     * SkuIsValid constructor.
     *
     * @param ResourceConnection $resource
     * @param BaseHelper $baseHelper
     * @param DataObjectFactory $dataObjectFactory
     */
    public function __construct(
        ResourceConnection $resource,
        BaseHelper $baseHelper,
        DataObjectFactory $dataObjectFactory,
        SkuValidator $skuValidator
    ) {
        $this->skuValidator = $skuValidator;
        parent::__construct($resource, $baseHelper, $dataObjectFactory);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return Helper::KEY_SKU_IS_VALID;
    }

    /**
     * {@inheritdoc}
     */
    public function prepareDataBeforeSave($value)
    {
        if (!isset($value['sku'])) {
            return false;
        }

        return $this->skuValidator->isOptionValueSkuIsValid($value['sku']);

    }

    /**
     * {@inheritdoc}
     */
    public function prepareDataForFrontend($object)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function importTemplateMageOne($data)
    {
        return 0;
    }

    /**
     * Prepare data for attributes, which do NOT have own database tables, for Magento2 product import
     *
     * @param array $data
     * @param string $type
     * @return mixed
     */
    public function prepareImportDataMageTwo($data, $type)
    {
        return empty($data['custom_option_row_' . $this->getName()])
            ? 0
            : $data['custom_option_row_' . $this->getName()];
    }
}
