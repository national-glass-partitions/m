<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionFeatures\Block;

use Magento\Framework\Registry;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionBase\Helper\System as SystemHelper;
use MageWorx\OptionFeatures\Helper\Data as Helper;
use MageWorx\OptionFeatures\Model\Config\Features as FeaturesConfig;

class Features extends Template
{
    protected EncoderInterface $jsonEncoder;
    protected Helper $helper;
    protected SystemHelper $systemHelper;
    protected BaseHelper $baseHelper;
    protected Registry $registry;
    protected FeaturesConfig $featuresConfig;
    protected array $selectionLimitCache = [];
    protected string $jsonData = '';
    protected string $isDefaultJsonData = '';

    public function __construct(
        Context $context,
        EncoderInterface $jsonEncoder,
        Helper $helper,
        SystemHelper $systemHelper,
        BaseHelper $baseHelper,
        Registry $registry,
        FeaturesConfig $featuresConfig,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $data
        );
        $this->jsonEncoder    = $jsonEncoder;
        $this->helper         = $helper;
        $this->systemHelper   = $systemHelper;
        $this->baseHelper     = $baseHelper;
        $this->registry       = $registry;
        $this->featuresConfig = $featuresConfig;
    }

    /**
     * @return string
     */
    public function getJsonData(): string
    {
        if (!empty($this->jsonData)) {
            return $this->jsonData;
        }

        $data = [
            'question_image'                        => $this->getViewFileUrl(
                'MageWorx_OptionFeatures::image/question.png'
            ),
            'value_description_enabled'             => $this->helper->isValueDescriptionEnabled(),
            'option_description_enabled'            => $this->helper->isOptionDescriptionEnabled(),
            'option_description_mode'               => $this->helper->getOptionDescriptionMode(),
            'option_description_modes'              => [
                'disabled' => Helper::OPTION_DESCRIPTION_DISABLED,
                'tooltip'  => Helper::OPTION_DESCRIPTION_TOOLTIP,
                'text'     => Helper::OPTION_DESCRIPTION_TEXT,
            ],
            'product_price_display_mode'            => $this->helper->getProductPriceDisplayMode(),
            'additional_product_price_display_mode' => $this->helper->getAdditionalProductPriceFieldMode()
        ];

        $storeId = $this->getProduct() ? $this->getProduct()->getStoreId() : 0;
        $data['shareable_link_hint_text'] = $this->helper->getShareableLinkHintText($storeId);

        $this->jsonData = (string)$this->jsonEncoder->encode($data);

        return $this->jsonData;
    }

    /**
     * @return \Magento\Catalog\Model\Product|null
     */
    protected function getProduct()
    {
        $product = $this->registry->registry('product');
        if (!$product || !$product->getId()) {
            return null;
        }
        return $product;
    }

    /**
     * @return string
     */
    public function getIsDefaultJsonData(): string
    {
        if (!empty($this->isDefaultJsonData)) {
            return $this->isDefaultJsonData;
        }

        $data = [
            'is_default_values' => $this->featuresConfig->getIsDefaultArray($this->registry->registry('product'))
        ];

        $this->isDefaultJsonData = (string)$this->jsonEncoder->encode($data);

        return $this->isDefaultJsonData;
    }

    /**
     * @return string
     */
    public function getSelectionLimitJsonData()
    {
        $data = [];

        $product = $this->getProduct();
        if (!$product) {
            return $this->jsonEncoder->encode($data);
        }

        if (!empty($this->selectionLimitCache[$product->getId()])) {
            return $this->selectionLimitCache[$product->getId()];
        }

        $options = $product->getOptions();
        foreach ($options as $option) {
            $data[$option->getOptionId()] = [
                'selection_limit_from' => $option->getSelectionLimitFrom(),
                'selection_limit_to'   => $option->getSelectionLimitTo()
            ];
        }

        return $this->selectionLimitCache[$product->getId()] = $this->jsonEncoder->encode($data);
    }
}
