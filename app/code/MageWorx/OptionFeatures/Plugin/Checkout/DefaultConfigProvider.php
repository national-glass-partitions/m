<?php
/**
 * Copyright © 2017 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionFeatures\Plugin\Checkout;

use Magento\Checkout\Model\DefaultConfigProvider as OriginalDefaultConfigProvider;
use Magento\Framework\Exception\NoSuchEntityException;
use MageWorx\OptionFeatures\Helper\Data as Helper;
use MageWorx\OptionFeatures\Model\Image as ImageModel;
use MageWorx\OptionFeatures\Model\ResourceModel\Image\Collection as ImagesCollection;
use MageWorx\OptionFeatures\Model\ResourceModel\Image\CollectionFactory as ImagesCollectionFactory;

/**
 * Class DefaultConfigProvider
 *
 * @package MageWorx\OptionFeatures\Plugin\Checkout
 *
 * Main goal is to replace quote item image in the checkout page to the corresponding image based on the custom options
 * selection.
 */
class DefaultConfigProvider
{
    protected ImagesCollectionFactory $imagesCollectionFactory;
    protected Helper $helper;

    public function __construct(
        ImagesCollectionFactory $imagesCollectionFactory,
        Helper $helper
    ) {
        $this->imagesCollectionFactory = $imagesCollectionFactory;
        $this->helper                  = $helper;
    }

    /**
     * Used for the image replacement in the checkout review section
     *
     * @param OriginalDefaultConfigProvider $subject
     * @param array $result
     * @return array
     */
    public function afterGetConfig(OriginalDefaultConfigProvider $subject, array $result)
    {
        if (empty($result['quoteItemData'])) {
            return $result;
        }

        foreach ($result['quoteItemData'] as $index => $quoteItemData) {
            if (empty($quoteItemData['product']['options'])) {
                continue;
            }

            $optionsToBeProcessed = [];
            /** @var \Magento\Catalog\Model\Product\Option $option */
            foreach ($quoteItemData['product']['options'] as $option) {
                if (empty($option[Helper::KEY_OPTION_IMAGE_MODE])) {
                    continue;
                }
                if ($option[Helper::KEY_OPTION_IMAGE_MODE] == Helper::OPTION_IMAGE_MODE_REPLACE) {
                    $optionsToBeProcessed['replace'][] = $option;
                } elseif ($option[Helper::KEY_OPTION_IMAGE_MODE] == Helper::OPTION_IMAGE_MODE_OVERLAY) {
                    $optionsToBeProcessed['overlay'][] = $option;
                }
            }

            if (empty($optionsToBeProcessed)) {
                continue;
            }

            $quoteItemId = $result['quoteItemData'][$index]['item_id'];
            /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
            $quoteItem = $this->findQuoteItemByIdInConfig($quoteItemId, $result);
            if (!$quoteItem) {
                continue;
            }

            $imageUrl = null;
            if (!empty($optionsToBeProcessed['replace'])) {
                $selectedValues = $this->helper->getSelectedValuesFromQuoteItem($optionsToBeProcessed['replace'], $quoteItem);
                if (!empty($selectedValues)) {
                    $imageUrl = $this->getReplaceImageUrl($index, $result, $optionsToBeProcessed['replace']);
                }
            }
            if (!empty($optionsToBeProcessed['overlay'])) {
                $selectedValues = $this->helper->getSelectedValuesFromQuoteItem($optionsToBeProcessed['overlay'], $quoteItem);
                if (!empty($selectedValues)) {
                    $imageUrl = $this->getOverlayImageUrl($index, $result, $imageUrl, $optionsToBeProcessed['overlay']);
                }
            }

            if ($imageUrl) {
                $result['quoteItemData'][$index]['thumbnail']          = $imageUrl;
                $result['imageData'][$quoteItemData['item_id']]['src'] = $imageUrl;
            }
        }

        return $result;
    }

    /**
     * Process overlay images
     *
     * @param int $index Quote Item index in config
     * @param array $result Config
     * @param string $imageUrl
     * @param \Magento\Catalog\Model\Product\Option[] $optionsToBeProcessed Options with processable image mode
     * @return string|null
     */
    private function getOverlayImageUrl($index, $result, $imageUrl, $optionsToBeProcessed)
    {
        if (empty($optionsToBeProcessed)) {
            return null;
        }

        $imageWidth    = 75;
        $imageHeight   = 75;
        $sortedOptions = $this->helper->sortOptions($optionsToBeProcessed);

        $quoteItemId = $result['quoteItemData'][$index]['item_id'];
        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        $quoteItem = $this->findQuoteItemByIdInConfig($quoteItemId, $result);
        if (!$quoteItem) {
            return null;
        }

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

        return (string)$this->helper->getOverlayImageUrl($imageUrl, $overlayImages, $imageWidth, $imageHeight);
    }

    /**
     * Search most suitable image using sort order and returns its URL
     *
     * @important Method uses recursion and can call itself if suitable image is not found
     * in the current option or value
     *
     * @param int $index Quote Item index in config
     * @param array $result Config
     * @param \Magento\Catalog\Model\Product\Option[] $optionsShouldBeProcessed Options with processable image mode
     * @return string|null
     */
    private function getReplaceImageUrl($index, $result, $optionsShouldBeProcessed)
    {
        if (empty($optionsShouldBeProcessed)) {
            return null;
        }

        $imageWidth    = 75;
        $imageHeight   = 75;
        $sortedOptions = $this->helper->sortOptions($optionsShouldBeProcessed);
        /** @var \Magento\Catalog\Model\Product\Option $lastOption */
        $lastOption   = end($sortedOptions);
        $lastOptionId = $lastOption->getId();
        $quoteItemId  = $result['quoteItemData'][$index]['item_id'];
        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        $quoteItem = $this->findQuoteItemByIdInConfig($quoteItemId, $result);
        if (!$quoteItem) {
            return null;
        }

        /** @var \Magento\Quote\Model\Quote\Item\Option $quoteItemOption */
        $quoteItemOption = $quoteItem->getOptionByCode('option_' . $lastOptionId);
        if (empty($quoteItemOption)) {
            return $this->renew($index, $result, $optionsShouldBeProcessed);
        }

        $optionValue          = $quoteItemOption->getValue();
        $optionValuesReversed = array_reverse(explode(',', $optionValue));
        foreach ($optionValuesReversed as $value) {
            /** @var \Magento\Catalog\Model\Product\Option\Value $valueModel */
            $valueModel = $lastOption->getValueById($value);
            if (!$valueModel || !$valueModel->getId()) {
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
            if (!$imageModel->getId() || !$imageModel->getValue()) {
                continue;
            }
            $imageUrl = $this->helper->getImageUrl($imageModel->getValue(), $imageHeight, $imageWidth);

            return (string)$imageUrl;
        }

        return (string)$this->renew($index, $result, $optionsShouldBeProcessed);
    }

    /**
     * Return quote item from config by its id
     *
     * @param int $id
     * @param array $config
     * @return \Magento\Quote\Model\Quote\Item|null
     */
    private function findQuoteItemByIdInConfig($id, array $config)
    {
        /** @var \Magento\Quote\Model\Quote\Item[] $items */
        $items = $config['quoteData']['items'];
        foreach ($items as $index => $item) {
            if ($item->getId() == $id) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Used for recursion call of the getSelectedOptionsImageUrl method
     * validate input data and breaks recursion if an input array (options) is empty
     *
     * @param $index
     * @param $result
     * @param $optionsShouldBeProcessed
     * @return string|null
     */
    private function renew($index, $result, $optionsShouldBeProcessed)
    {
        if (empty($optionsShouldBeProcessed)) {
            return null;
        }

        array_pop($optionsShouldBeProcessed);

        return (string)$this->getReplaceImageUrl($index, $result, $optionsShouldBeProcessed);
    }
}
