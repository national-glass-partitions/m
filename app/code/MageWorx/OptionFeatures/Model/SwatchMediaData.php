<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionFeatures\Model;

use Magento\Framework\Serialize\Serializer\Json as Serializer;
use Magento\Catalog\Api\ProductCustomOptionTypeListInterface;
use Magento\Catalog\Model\Product\Option as ProductOption;
use Magento\Framework\Image\Factory as ImageFactory;
use MageWorx\OptionFeatures\Helper\Data as Helper;
use MageWorx\OptionFeatures\Model\ResourceModel\Image\Collection as ImagesCollection;
use MageWorx\OptionFeatures\Model\ResourceModel\Image\CollectionFactory as ImagesCollectionFactory;
use MageWorx\OptionFeatures\Model\Product\Option\Value\Media\Config as MediaConfig;
use MageWorx\OptionFeatures\Model\Image as ImageModel;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\View\LayoutInterface;

class SwatchMediaData
{
    protected ImagesCollectionFactory $imagesCollectionFactory;
    protected Helper $helper;
    protected array $mediaAttributes = [];
    protected ProductCustomOptionTypeListInterface $customOptionTypeList;
    protected MediaConfig $mediaConfig;
    protected Serializer $serializer;
    protected array $imageConfigCache = [];
    protected LayoutInterface $layout;
    protected \Magento\Framework\App\State $state;

    /**
     * SwatchMediaData constructor.
     *
     * @param Helper $helper
     * @param ImagesCollectionFactory $imagesCollectionFactory
     * @param ProductCustomOptionTypeListInterface $customOptionTypeList
     * @param MediaConfig $mediaConfig
     * @param Serializer $serializer
     */
    public function __construct(
        Helper $helper,
        ImagesCollectionFactory $imagesCollectionFactory,
        ProductCustomOptionTypeListInterface $customOptionTypeList,
        MediaConfig $mediaConfig,
        Serializer $serializer,
        \Magento\Framework\App\State $state,
        LayoutInterface $layout
    ) {
        $this->helper                  = $helper;
        $this->imagesCollectionFactory = $imagesCollectionFactory;
        $this->customOptionTypeList    = $customOptionTypeList;
        $this->mediaConfig             = $mediaConfig;
        $this->serializer              = $serializer;
        $this->state                   = $state;
        $this->layout                  = $layout;
    }

    /**
     * @param ProductInterface $product
     * @param int|null $width
     * @param int|null $height
     * @return bool|false|string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getSwatchMediaData(ProductInterface $product, int $width = null, int $height = null): string
    {
        $valueIds        = [];
        $valueOptionMap  = [];
        $options         = $product->getOptions();
        $data['options'] = [];
        /** @var ProductOption $option */
        foreach ($options as $option) {
            /** @var int $optionId */
            $optionId = $option->getId();
            if (!in_array($option->getType(), $this->getOptionTypesWithImages()) || !$option->getValues()) {
                continue;
            }
            $data['options'][$optionId] = [
                'type'                        => $option->getType(),
                'mageworx_option_gallery'     => $option->getData('mageworx_option_gallery'),
                Helper::KEY_OPTION_IMAGE_MODE => $option->getData(Helper::KEY_OPTION_IMAGE_MODE),
                'sort_order'                  => $option->getSortOrder(),
            ];
            $values                     = $option->getValues();

            foreach ($values as $valueId => $value) {
                $data['options'][$optionId]['values'][$valueId]['sort_order'] = $value->getSortOrder();
                $valueIds[]                                                   = $value->getData('option_type_id');
                $valueOptionMap[$valueId]                                     = $optionId;
            }
        }

        $collection = $this->imagesCollectionFactory
            ->create()
            ->addFieldToFilter(
                'option_type_id',
                ['in' => $valueIds]
            );

        foreach ($collection->getItems() as $collectionItem) {
            $valueId  = $collectionItem->getOptionTypeId();
            $optionId = $valueOptionMap[$valueId];

            $data['options'][$optionId]['values'][$valueId]['images'][$collectionItem->getOptionTypeImageId()] = [
                'value_id'                                    => $collectionItem->getOptionTypeImageId(),
                'option_type_id'                              => $collectionItem->getOptionTypeId(),
                'position'                                    => $collectionItem->getSortOrder(),
                'file'                                        => $collectionItem->getValue(),
                'label'                                       => $collectionItem->getTitleText(),
                'custom_media_type'                           => $collectionItem->getMediaType(),
                'color'                                       => $collectionItem->getColor(),
                ImageModel::COLUMN_HIDE_IN_GALLERY            =>
                    $collectionItem->getData(ImageModel::COLUMN_HIDE_IN_GALLERY),
                'url'                                         => $this->helper->getThumbImageUrl(
                    $collectionItem->getValue(),
                    Helper::IMAGE_MEDIA_ATTRIBUTE_BASE_IMAGE
                ),
                ImageModel::COLUMN_REPLACE_MAIN_GALLERY_IMAGE =>
                    $collectionItem->getData(ImageModel::COLUMN_REPLACE_MAIN_GALLERY_IMAGE),
                ImageModel::COLUMN_OVERLAY_IMAGE              =>
                    $collectionItem->getData(ImageModel::COLUMN_OVERLAY_IMAGE),
                ImageModel::COLUMN_BASE_IMAGE                 =>
                    $collectionItem->getData(ImageModel::COLUMN_BASE_IMAGE),
                ImageModel::COLUMN_TOOLTIP_IMAGE              =>
                    $collectionItem->getData(ImageModel::COLUMN_TOOLTIP_IMAGE)
            ];
            if ($collectionItem->getData(ImageModel::COLUMN_REPLACE_MAIN_GALLERY_IMAGE)) {
                $data['options'][$optionId]['values'][$valueId]['images'][$collectionItem->getOptionTypeImageId(
                )]['full']  =
                    $this->getImageUrl($collectionItem->getValue());
                $data['options'][$optionId]['values'][$valueId]['images'][$collectionItem->getOptionTypeImageId(
                )]['img']   =
                    $this->getImageUrl($collectionItem->getValue());
                $data['options'][$optionId]['values'][$valueId]['images'][$collectionItem->getOptionTypeImageId(
                )]['thumb'] =
                    $this->getImageUrl($collectionItem->getValue(), 'product_page_image_small', $height, $width);
            }

            /**
             * init overlay image field
             */
            if(!isset($data['options'][$optionId]['values'][$valueId]['overlay_image_url'])) {
                $data['options'][$optionId]['values'][$valueId]['overlay_image_url'] = '';
            }

            if ($collectionItem->getData(ImageModel::COLUMN_OVERLAY_IMAGE)) {
                $data['options'][$optionId]['values'][$valueId]['overlay_image_url'] =
                    $this->getImageUrl($collectionItem->getValue());
            }
        }

        $data['option_types']                   = $this->getOptionTypes();
        $data['render_images_for_option_types'] = $this->getOptionTypesWithImages();
        $data['option_gallery_type']            = [
            'disabled'      => Helper::OPTION_GALLERY_TYPE_DISABLED,
            'beside_option' => Helper::OPTION_GALLERY_TYPE_BESIDE_OPTION,
            'once_selected' => Helper::OPTION_GALLERY_TYPE_ONCE_SELECTED,
        ];

        return $this->imageConfigCache[$product->getId()] = $this->serializer->serialize($data);
    }

    /**
     * Return array with option types with images (option gallery)
     *
     * @return array
     */
    public function getOptionTypesWithImages(): array
    {
        return [
            ProductOption::OPTION_TYPE_DROP_DOWN,
            ProductOption::OPTION_TYPE_RADIO,
            ProductOption::OPTION_TYPE_CHECKBOX,
            ProductOption::OPTION_TYPE_MULTIPLE
        ];
    }

    /**
     * Get image url for specified type, width or height
     *
     * @param string $path
     * @param string|null $type
     * @param int|null $height
     * @param int|null $width
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getImageUrl(string $path, string $type = null, int $height = null, int $width = null): string
    {
        if (!$path) {
            return '';
        }

        if ($type && $this->state->getAreaCode() != 'graphql') {
            /** @var \Magento\Catalog\Block\Product\View\Gallery $galleryBlock */
            $galleryBlock = $this->layout->getBlockSingleton('Magento\Catalog\Block\Product\View\Gallery');
            $width        = $galleryBlock->getImageAttribute($type, 'width');
            $height       = $galleryBlock->getImageAttribute($type, 'height');
        } elseif (!$height && !$width) {
            return $this->mediaConfig->getMediaUrl($path);
        }

        return $this->helper->getImageUrl($path, $height, $width);
    }

    /**
     * Get all available option types in array
     *
     * @return array
     */
    public function getOptionTypes(): array
    {
        /** @var \Magento\Catalog\Api\Data\ProductCustomOptionTypeInterface[] $typesList */
        $typesList       = $this->customOptionTypeList->getItems();
        $optionTypeCodes = [];
        foreach ($typesList as $type) {
            $optionTypeCodes[] = $type->getCode();
        }

        return $optionTypeCodes;
    }
}
