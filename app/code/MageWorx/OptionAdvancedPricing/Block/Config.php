<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionAdvancedPricing\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Registry;
use MageWorx\OptionBase\Model\ResourceModel\Option as OptionModel;
use MageWorx\OptionBase\Helper\Data as BaseHelper;

class Config extends Template
{
    protected BaseHelper $baseHelper;
    protected OptionModel $optionModel;
    protected Registry $registry;
    protected string $jsonData = '';

    public function __construct(
        Context $context,
        BaseHelper $baseHelper,
        OptionModel $optionModel,
        Registry $registry,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $data
        );
        $this->baseHelper  = $baseHelper;
        $this->optionModel = $optionModel;
        $this->registry    = $registry;
    }

    /**
     * @return string
     */
    public function getJsonData()
    {
        if (!empty($this->jsonData)) {
            return $this->jsonData;
        }

        $data = [
            'optionTypes' => $this->getOptionTypes()
        ];

        $this->jsonData = $this->baseHelper->jsonEncode($data);

        return $this->jsonData;
    }

    /**
     * Get option types ('option_id' => 'type') in json
     *
     * @return array
     */
    public function getOptionTypes()
    {
        $linkField = $this->baseHelper->getLinkField();
        $product = $this->registry->registry('product');

        return $this->optionModel->getOptionTypes($product->getData($linkField));
    }
}
