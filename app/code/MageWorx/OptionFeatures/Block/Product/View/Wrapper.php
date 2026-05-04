<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionFeatures\Block\Product\View;

use Magento\Framework\Serialize\Serializer\Json as Serializer;
use Magento\Framework\View\Element\Template;
use MageWorx\OptionFeatures\Model\SwatchMediaData;

/**
 * Class Wrapper
 *
 * @package MageWorx\OptionSwatches\Block\Product\View
 *
 * Main goal is to provide image data for the frontend widgets:
 * @see MageWorx/OptionFeatures/view/frontend/web/js/swatches.js
 * @see MageWorx/OptionFeatures/view/frontend/web/js/swatches/additional.js
 */
class Wrapper extends Template
{
    protected SwatchMediaData $swatchMediaData;
    protected array $imageConfigCache = [];
    protected Serializer $serializer;

    /**
     * Wrapper constructor.
     *
     * @param Template\Context $context
     * @param array $data
     * @param Serializer $serializer
     * @param SwatchMediaData $swatchMediaData
     */
    public function __construct(
        Template\Context $context,
        array $data,
        Serializer $serializer,
        SwatchMediaData $swatchMediaData
    ) {
        parent::__construct($context, $data);
        $this->serializer      = $serializer;
        $this->swatchMediaData = $swatchMediaData;
    }

    /**
     * @return string
     */
    public function getJsonParams()
    {
        $data = [];

        return $this->serializer->serialize($data);
    }

    /**
     * Returns JSON config for the frontend swatch-widgets
     *
     * @important Do not remove any data from method without testing frontend! because frontend widget depends on it!
     *
     * @return mixed|string|void
     * @see MageWorx/OptionSwatches/view/frontend/web/js/swatches/additional.js
     *
     * @see MageWorx/OptionSwatches/view/frontend/web/js/swatches.js
     */
    public function getAllOptionsJson()
    {
        $data = [];

        /** @var \Magento\Catalog\Block\Product\View $productMainBlock */
        $productMainBlock = $this->getLayout()->getBlockSingleton('Magento\Catalog\Block\Product\View');
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $productMainBlock->getProduct();
        if (!$product || !$product->getId()) {
            return $this->serializer->serialize($data);
        }

        if (!empty($this->imageConfigCache[$product->getId()])) {
            return $this->imageConfigCache[$product->getId()];
        }

        return $this->swatchMediaData->getSwatchMediaData($product);
    }
}
