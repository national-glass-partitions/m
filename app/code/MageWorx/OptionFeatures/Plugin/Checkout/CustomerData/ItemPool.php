<?php
/**
 * Copyright © 2017 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionFeatures\Plugin\Checkout\CustomerData;

use Magento\Checkout\CustomerData\ItemPool as OriginalItemPool;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use MageWorx\OptionFeatures\Helper\Data as Helper;
use MageWorx\OptionFeatures\Model\Image as ImageModel;
use MageWorx\OptionFeatures\Model\ResourceModel\Image\Collection as ImagesCollection;
use MageWorx\OptionFeatures\Model\ResourceModel\Image\CollectionFactory as ImagesCollectionFactory;
use MageWorx\OptionFeatures\Ui\DataProvider\Product\Form\Modifier\Features;

/**
 * Class ItemPool
 * @package MageWorx\OptionFeatures\Plugin\Checkout\CustomerData
 *
 * Main goal is to replace quote item image in the cart sidebar to the corresponding image based on the custom options
 * selection.
 */
class ItemPool
{
    protected ImagesCollectionFactory $imagesCollectionFactory;
    protected Helper $helper;

    /**
     * ItemPool constructor.
     * @param ImagesCollectionFactory $imagesCollectionFactory
     * @param Helper $helper
     */
    public function __construct(
        ImagesCollectionFactory $imagesCollectionFactory,
        Helper $helper
    ) {
        $this->imagesCollectionFactory = $imagesCollectionFactory;
        $this->helper = $helper;
    }

    /**
     * Used for the image replacement in the cart sidebar (not a regular cart page!)
     * For regular cart @see \MageWorx\OptionFeatures\Plugin\Cart\Item\Renderer
     *
     * @param OriginalItemPool $subject
     * @param \Closure $proceed
     * @param QuoteItem $item
     * @return array
     */
    public function aroundGetItemData(OriginalItemPool $subject, \Closure $proceed, QuoteItem $item)
    {
        $result = $proceed($item);
        if (empty($result)) {
            return $result;
        }

        if (empty($result['options'])) {
            return $result;
        }

        $optionsToBeProcessed = [];
        // Check image mode in all options
        foreach ($result['options'] as $optionData) {
            if (empty($optionData['option_id'])) {
                continue;
            }

            $option = $item->getOptionByCode('option_' . $optionData['option_id']);
            if (empty($option)) {
                continue;
            }

            /** @var \Magento\Catalog\Model\Product $product */
            $product = $item->getProduct();
            $productOption = $product->getOptionById($optionData['option_id']);
            if (empty($productOption[Helper::KEY_OPTION_IMAGE_MODE])) {
                continue;
            }
            if ($productOption[Helper::KEY_OPTION_IMAGE_MODE] == Helper::OPTION_IMAGE_MODE_REPLACE) {
                $optionsToBeProcessed['replace'][] = $productOption;
            } elseif ($productOption[Helper::KEY_OPTION_IMAGE_MODE] == Helper::OPTION_IMAGE_MODE_OVERLAY) {
                $optionsToBeProcessed['overlay'][] = $productOption;
            }
        }

        // Do nothing with product without replace mode
        if (empty($optionsToBeProcessed)) {
            return $result;
        }

        $imageData = null;
        if (!empty($optionsToBeProcessed['replace'])) {
            $imageData = $this->getReplaceImageData($optionsToBeProcessed['replace'], $item);
        }
        if (!empty($optionsToBeProcessed['overlay'])) {
            if (!$imageData && isset($result['product_image'])) {
                $imageData = $result['product_image'];
            }
            $imageData = $this->getOverlayImageData($optionsToBeProcessed['overlay'], $imageData, $item);
        }

        if (!empty($imageData)) {
            $result['product_image'] = $imageData;
        }

        return $result;
    }

    /**
     * Search most suitable image using sort order and returns its data in array:
     * 'src' => string image url in pub/media,
     * 'alt' => string,
     * 'width' => int,
     * 'height' => int
     *
     * @important Method uses recursion and can call itself if suitable image is not found
     * in the current option or value
     *
     * @param \Magento\Catalog\Model\Product\Option[] $optionsToBeProcessed Options with processable image mode
     * @param array $imageData
     * @param QuoteItem $quoteItem
     * @return array
     */
    private function getOverlayImageData(array $optionsToBeProcessed, $imageData, QuoteItem $quoteItem)
    {
        if (empty($optionsToBeProcessed)) {
            return null;
        }

        $imageHeight = 75;
        $imageWidth  = 75;

        $selectedValues = $this->helper->getSelectedValuesFromQuoteItem($optionsToBeProcessed, $quoteItem);

        /** @var ImagesCollection $imageCollection */
        $imageCollection = $this->imagesCollectionFactory
            ->create()
            ->addFieldToFilter(
                'option_type_id',
                $selectedValues
            )->addFieldToFilter(
                'overlay_image',
                1
            );

        $overlayImages = [];
        foreach ($imageCollection->getItems() as $overlayImage) {
            if (!$overlayImage || !$overlayImage->getValue()) {
                continue;
            }

            $overlayImages[] = $overlayImage;
        }

        $baseImageUrl = $imageData['src'] ?? '';
        if (is_object($imageData) && !$baseImageUrl) {
            $baseImageUrl = $imageData->getUrl();
        }
        $imageUrl = $this->helper->getOverlayImageUrl($baseImageUrl, $overlayImages, $imageWidth, $imageHeight);

        $alt = $imageData['label'] ?? '';
        if (is_object($imageData) && !$alt) {
            $alt = $imageData->getAlt();
        }

        $data = [
            'src' => $imageUrl,
            'alt' => $alt,
            'width' => $imageWidth,
            'height' => $imageHeight,
        ];

        return $data;
    }

    /**
     * Search most suitable image using sort order and returns its data in array:
     * 'src' => string image url in pub/media,
     * 'alt' => string,
     * 'width' => int,
     * 'height' => int
     *
     * @important Method uses recursion and can call itself if suitable image is not found
     * in the current option or value
     *
     * @param \Magento\Catalog\Model\Product\Option[] $optionsShouldBeProcessed Options with processable image mode
     * @param QuoteItem $quoteItem
     * @return array
     */
    private function getReplaceImageData(array $optionsShouldBeProcessed, QuoteItem $quoteItem)
    {
        if (empty($optionsShouldBeProcessed)) {
            return null;
        }

        $imageHeight = 75;
        $imageWidth = 75;
        $sortedOptions = $this->helper->sortOptions($optionsShouldBeProcessed);
        /** @var \Magento\Catalog\Model\Product\Option $lastOption */
        $lastOption = end($sortedOptions);
        $lastOptionId = $lastOption->getId();
        /** @var \Magento\Quote\Model\Quote\Item\Option $quoteItemOption */
        $quoteItemOption = $quoteItem->getOptionByCode('option_' . $lastOptionId);
        if (!$quoteItemOption) {
            return $this->renew($optionsShouldBeProcessed, $quoteItem);
        }

        $optionValue = $quoteItemOption->getValue();
        if (!$optionValue) {
            return $this->renew($optionsShouldBeProcessed, $quoteItem);
        }

        $optionValuesReversed = array_reverse(explode(',', $optionValue));
        foreach ($optionValuesReversed as $value) {
            $valueModel = $lastOption->getValueById($value);
            if (!$valueModel) {
                continue;
            }
            /** @var ImagesCollection $imageCollection */
            $imageCollection = $this->imagesCollectionFactory
                ->create()
                ->addFieldToFilter(
                    'option_type_id',
                    $valueModel->getData('option_type_id')
                )->addFieldToFilter(
                    'replace_main_gallery_image',
                    1
                );
            /** @var ImageModel $imageModel */
            $imageModel = $imageCollection->getFirstItem();
            if (!$imageModel || !$imageModel->getValue()) {
                continue;
            }

            $imageUrl = $this->helper->getImageUrl($imageModel->getValue(), $imageHeight, $imageWidth);
            $data = [
                'src' => $imageUrl,
                'alt' => $imageModel->getAlt(),
                'width' => $imageWidth,
                'height' => $imageHeight,
            ];

            return $data;
        }

        return $this->renew($optionsShouldBeProcessed, $quoteItem);
    }

    /**
     * Used for recursion call of the getSelectedOptionsImageData method
     * validate input data and breaks recursion if an input array (options) is empty
     *
     * @param array $optionsShouldBeProcessed
     * @param QuoteItem $quoteItem
     * @return array|null
     */
    private function renew(array $optionsShouldBeProcessed, QuoteItem $quoteItem)
    {
        if (empty($optionsShouldBeProcessed)) {
            return null;
        }

        array_pop($optionsShouldBeProcessed);

        return $this->getReplaceImageData($optionsShouldBeProcessed, $quoteItem);
    }
}
