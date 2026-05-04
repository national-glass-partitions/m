<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionFeatures\Model\Attribute\OptionValue;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Filesystem;
use Magento\Framework\Serialize\Serializer\Json as Serializer;
use MageWorx\OptionFeatures\Helper\Data as Helper;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionFeatures\Model\Image as ImageModel;
use MageWorx\OptionFeatures\Model\ResourceModel\Image\Collection as ImageCollection;
use MageWorx\OptionFeatures\Model\ImageFactory;
use MageWorx\OptionBase\Model\Product\Option\AbstractAttribute;
use MageWorx\OptionFeatures\Model\Product\Option\Value\Media\Config;

class Image extends AbstractAttribute
{
    const FIELD_MAGE_ONE_OPTIONS_IMPORT = '_custom_option_row_image_data';

    protected Helper $helper;
    protected ImageFactory $imageFactory;
    protected ImageCollection $imageCollection;
    protected Serializer $serializer;
    protected Config $mediaConfig;
    protected Filesystem $filesystem;

    /**
     * Image constructor.
     *
     * @param ResourceConnection $resource
     * @param ImageFactory $imageFactory
     * @param ImageCollection $imageCollection
     * @param DataObjectFactory $dataObjectFactory
     * @param BaseHelper $baseHelper
     * @param Helper $helper
     * @param Serializer $serializer
     * @param Config $mediaConfig
     * @param Filesystem $filesystem
     */
    public function __construct(
        ResourceConnection $resource,
        ImageFactory $imageFactory,
        ImageCollection $imageCollection,
        DataObjectFactory $dataObjectFactory,
        BaseHelper $baseHelper,
        Helper $helper,
        Serializer $serializer,
        Config $mediaConfig,
        Filesystem $filesystem
    ) {
        $this->helper          = $helper;
        $this->imageFactory    = $imageFactory;
        $this->imageCollection = $imageCollection;
        $this->serializer      = $serializer;
        $this->filesystem      = $filesystem;
        $this->mediaConfig     = $mediaConfig;
        parent::__construct($resource, $baseHelper, $dataObjectFactory);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return Helper::KEY_IMAGE;
    }

    /**
     * {@inheritdoc}
     */
    public function hasOwnTable()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getTableName($type = '')
    {
        $map = [
            'product' => ImageModel::TABLE_NAME,
            'group'   => ImageModel::OPTIONTEMPLATES_TABLE_NAME
        ];
        if (!$type) {
            return $map[$this->entity->getType()];
        }

        return $map[$type];
    }

    /**
     * {@inheritdoc}
     */
    public function collectData($entity, array $options)
    {
        $this->entity = $entity;

        $images = [];
        foreach ($options as $option) {
            if (empty($option['values'])) {
                continue;
            }
            foreach ($option['values'] as $value) {
                if (!isset($value[Helper::KEY_IMAGE])) {
                    continue;
                }
                $data = json_decode($value[Helper::KEY_IMAGE], true);
                if (json_last_error() == JSON_ERROR_NONE) {
                    $images[$value['option_type_id']] = $data;
                } else {
                    parse_str($value[Helper::KEY_IMAGE], $images[$value['option_type_id']]);
                }
            }
        }

        return $this->collectImages($images);
    }

    /**
     * Save images
     *
     * @param array $items
     * @return array
     */
    protected function collectImages($items)
    {
        $data = [];
        foreach ($items as $imageKey => $images) {
            if (isset($images['optionfeatures']['media_gallery']['images'])) {
                $data['delete'][] = [
                    ImageModel::COLUMN_OPTION_TYPE_ID => $imageKey
                ];

                foreach ($images['optionfeatures']['media_gallery']['images'] as $imageItem) {
                    if (!empty($imageItem['removed'])) {
                        continue;
                    }
                    $imageText = $this->removeSpecialChars($imageItem['label']);
                    $imageData = [
                        'option_type_id'                   => $imageKey,
                        'sort_order'                       => $imageItem['position'],
                        'title_text'                       => htmlspecialchars($imageText, ENT_COMPAT, 'UTF-8', false),
                        'media_type'                       => $imageItem['custom_media_type'],
                        'color'                            => $imageItem['color'],
                        'value'                            => $imageItem['file'],
                        ImageModel::COLUMN_HIDE_IN_GALLERY => $imageItem[ImageModel::COLUMN_HIDE_IN_GALLERY],
                    ];
                    foreach ($this->helper->getImageAttributes() as $attributeCode => $attributeLabel) {
                        if (isset($images[$attributeCode])
                            && $imageItem['file']
                            && $images[$attributeCode] == $imageItem['file']
                        ) {
                            $imageData[$attributeCode] = true;
                        } else {
                            $imageData[$attributeCode] = false;
                        }

                        //adding roles for images in template import process from m1 to m2
                        if (isset($images['isImportTemplateMageOne'], $imageItem[$attributeCode])) {
                            $imageData[$attributeCode] = $imageItem[$attributeCode];
                        }
                    }
                    $data['save'][] = $imageData;
                }
            } elseif (!empty($images) && !isset($images['base_image'])) {
                $data['delete'][] = [
                    ImageModel::COLUMN_OPTION_TYPE_ID => $imageKey
                ];

                foreach ($images as $imageItem) {
                    if (!empty($imageItem['removed'])) {
                        continue;
                    }
                    $imageText = $this->removeSpecialChars($imageItem['title_text']);
                    $imageData = [
                        'option_type_id'                   => $imageKey,
                        'sort_order'                       => $imageItem['sort_order'],
                        'title_text'                       => htmlspecialchars($imageText, ENT_COMPAT, 'UTF-8', false),
                        'media_type'                       => $imageItem['custom_media_type'],
                        'color'                            => $imageItem['color'],
                        'value'                            => $imageItem['value'],
                        ImageModel::COLUMN_HIDE_IN_GALLERY => $imageItem[ImageModel::COLUMN_HIDE_IN_GALLERY],
                    ];
                    foreach ($this->helper->getImageAttributes() as $attributeCode => $attributeLabel) {
                        if (isset($imageItem[$attributeCode])) {
                            $imageData[$attributeCode] = $imageItem[$attributeCode];
                        }
                    }
                    $data['save'][] = $imageData;
                }
            } else {
                $data['delete'][] = [
                    ImageModel::COLUMN_OPTION_TYPE_ID => $imageKey
                ];
            }
        }

        return $data;
    }

    /**
     * Delete old option value images
     *
     * @param array $data
     * @return void
     */
    public function deleteOldData(array $data)
    {
        $optionValueIds = [];
        foreach ($data as $dataItem) {
            $optionValueIds[] = $dataItem[ImageModel::COLUMN_OPTION_TYPE_ID];
        }
        if (!$optionValueIds) {
            return;
        }
        $tableName  = $this->resource->getTableName($this->getTableName());
        $conditions = ImageModel::COLUMN_OPTION_TYPE_ID .
            ' IN (' . "'" . implode("','", $optionValueIds) . "'" . ')';
        $this->resource->getConnection()->delete(
            $tableName,
            $conditions
        );
    }

    /**
     * {@inheritdoc}
     */
    public function prepareDataForFrontend($object)
    {
        $imagesData             = [];
        $tooltipImage           = '';
        $configTooltipImageSize = $this->helper->getTooltipImageThumbnailSize();
        if (!empty($object->getTooltipImage())) {
            $tooltipImagePath = $object->getTooltipImage();
            $tooltipImage     = $this->helper->getThumbImageUrl(
                $tooltipImagePath,
                Helper::IMAGE_MEDIA_ATTRIBUTE_TOOLTIP_IMAGE
            );

            $filePath         = $this->mediaConfig->getMediaPath($tooltipImagePath);
            $fileAbsolutePath = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath($filePath);
            try {
                $imageSizeData    = getimagesize($fileAbsolutePath);
            } catch (\Exception $e) {
                // Skip if image is not available
                $imageSizeData = [];
            }

            if ($imageSizeData && $imageSizeData[0] < $configTooltipImageSize) {
                $imagesData['image_width'] = $imageSizeData[0];
            } else {
                $imagesData['image_width'] = $configTooltipImageSize;
            }
        };
        $imagesData['tooltip_image'] = $tooltipImage;


        return [$this->getName() => $imagesData];
    }

    /**
     * Remove backslashes and new line symbols from string
     *
     * @param $string string
     * @return string
     */
    public function removeSpecialChars($string): string
    {
        $string = str_replace(["\n", "\r"], '', (string)$string);

        return stripslashes($string);
    }

    /**
     * Process attribute in case of product/group duplication
     *
     * @param string $newId
     * @param string $oldId
     * @param string $entityType
     * @return void
     */
    public function processDuplicate($newId, $oldId, $entityType = 'product')
    {
        $connection = $this->resource->getConnection();
        $table      = $this->resource->getTableName($this->getTableName($entityType));

        $select = $connection->select()->from(
            $table,
            [
                new \Zend_Db_Expr($newId),
                ImageModel::COLUMN_MEDIA_TYPE,
                ImageModel::COLUMN_VALUE,
                ImageModel::COLUMN_TITLE_TEXT,
                ImageModel::COLUMN_SORT_ORDER,
                ImageModel::COLUMN_BASE_IMAGE,
                ImageModel::COLUMN_TOOLTIP_IMAGE,
                ImageModel::COLUMN_OVERLAY_IMAGE,
                ImageModel::COLUMN_COLOR,
                ImageModel::COLUMN_REPLACE_MAIN_GALLERY_IMAGE,
                ImageModel::COLUMN_HIDE_IN_GALLERY
            ]
        )->where(
            ImageModel::COLUMN_OPTION_TYPE_ID . ' = ?',
            $oldId
        );

        $insertSelect = $connection->insertFromSelect(
            $select,
            $table,
            [
                ImageModel::COLUMN_OPTION_TYPE_ID,
                ImageModel::COLUMN_MEDIA_TYPE,
                ImageModel::COLUMN_VALUE,
                ImageModel::COLUMN_TITLE_TEXT,
                ImageModel::COLUMN_SORT_ORDER,
                ImageModel::COLUMN_BASE_IMAGE,
                ImageModel::COLUMN_TOOLTIP_IMAGE,
                ImageModel::COLUMN_OVERLAY_IMAGE,
                ImageModel::COLUMN_COLOR,
                ImageModel::COLUMN_REPLACE_MAIN_GALLERY_IMAGE,
                ImageModel::COLUMN_HIDE_IN_GALLERY
            ]
        );
        $connection->query($insertSelect);
    }

    /**
     * {@inheritdoc}
     */
    public function importTemplateMageOne($data)
    {
        if (empty($data['images_data']) || !is_array($data['images_data'])) {
            return '';
        }

        $images            = [];
        $counter           = 1;
        $excludeFirstImage = (int)$data['exclude_first_image'];
        $imageMode         = (int)$data['image_mode'];
        foreach ($data['images_data'] as $fileName) {

            $overlayImage = false;
            $replaceImage = false;
            if ((!$excludeFirstImage && $counter === 1) || ($excludeFirstImage && $counter === 2)) {
                switch ($imageMode) {
                    case 2:
                        $overlayImage = false;
                        $replaceImage = true;
                        break;
                    case 4:
                        $overlayImage = true;
                        $replaceImage = false;
                        break;
                }
            }

            $images['optionfeatures']['media_gallery']['images'][] = [
                'media_type'                                  => 'image',
                'custom_media_type'                           => 'image',
                'file'                                        => $fileName,
                'label'                                       => '',
                'position'                                    => $counter,
                'disabled'                                    => 0,
                'value_id'                                    => '',
                'color'                                       => '',
                'removed'                                     => '',
                ImageModel::COLUMN_OVERLAY_IMAGE              => $overlayImage,
                ImageModel::COLUMN_REPLACE_MAIN_GALLERY_IMAGE => $replaceImage
            ];
            if ($counter == 1) {
                $images['base_image'] = $fileName;
            }
            $images['isImportTemplateMageOne'] = true;
            $counter++;
        }

        return $this->serializer->serialize($images);
    }

    /**
     * {@inheritdoc}
     */
    public function importTemplateMageTwo($data)
    {
        return $data[$this->getName()] ?? null;
    }

    /**
     * Prepare data from Magento 1 product csv for future import
     *
     * @param array $systemData
     * @param array $productData
     * @param array $optionData
     * @param array $preparedOptionData
     * @param array $valueData
     * @param array $preparedValueData
     * @return void
     */
    public function prepareOptionsMageOne(
        $systemData,
        $productData,
        $optionData,
        &$preparedOptionData,
        $valueData = [],
        &$preparedValueData = []
    ) {
        if (!isset($valueData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT])
            || $valueData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT] === ''
        ) {
            return;
        }

        $i                 = 1;
        $data              = [];
        $excludeFirstImage = (int)$optionData['_custom_option_exclude_first_image'];
        $imageMode         = (int)$optionData['_custom_option_image_mode'];
        $images            = explode('|', $valueData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT]);
        foreach ($images as $image) {
            list($imageFile, $sortOrder, $source) = explode(':', $image);

            if ($source == 2) {
                $color     = substr($imageFile, 1);
                $file      = '/'
                    . substr($color, 0, 1)
                    . '/'
                    . substr($color, 1, 1)
                    . '/'
                    . $color
                    . '.jpg';
                $mediaType = 'color';
            } else {
                $filePathArray = explode('/', $imageFile);
                if (!$filePathArray || !is_array($filePathArray)) {
                    continue;
                }
                $fileName  = end($filePathArray);
                $file      = '/'
                    . strtolower(substr($fileName, 0, 1))
                    . '/'
                    . strtolower(substr($fileName, 1, 1))
                    . '/'
                    . $fileName;
                $color     = '';
                $mediaType = 'image';
            }

            $overlayImage = false;
            $replaceImage = false;
            if ((!$excludeFirstImage && $i === 1) || ($excludeFirstImage && $i === 2)) {
                switch ($imageMode) {
                    case 2:
                        $overlayImage = false;
                        $replaceImage = true;
                        break;
                    case 4:
                        $overlayImage = true;
                        $replaceImage = false;
                        break;
                }
            }

            $data[] = [
                ImageModel::COLUMN_VALUE                      => $file,
                ImageModel::COLUMN_COLOR                      => $color,
                ImageModel::COLUMN_SORT_ORDER                 => $sortOrder,
                ImageModel::COLUMN_BASE_IMAGE                 => (int)($i === 1),
                ImageModel::COLUMN_TOOLTIP_IMAGE              => 0,
                ImageModel::COLUMN_OVERLAY_IMAGE              => $overlayImage,
                ImageModel::COLUMN_REPLACE_MAIN_GALLERY_IMAGE => $replaceImage,
                ImageModel::COLUMN_HIDE_IN_GALLERY            => 0,
                ImageModel::COLUMN_TITLE_TEXT                 => '',
                'custom_media_type'                           => $mediaType
            ];
            $i++;
        }

        $preparedValueData[static::getName()] = $this->baseHelper->jsonEncode($data);
    }

    /**
     * Collect data for magento2 product export
     *
     * @param array $row
     * @param array $data
     * @return void
     */
    public function collectExportDataMageTwo(&$row, $data)
    {
        $prefix        = 'custom_option_row_';
        $attributeData = null;
        if (!empty($data[$this->getName()])) {
            $attributeData = $this->baseHelper->jsonDecode($data[$this->getName()]);
        }
        if (empty($attributeData) || !is_array($attributeData)) {
            $row[$prefix . $this->getName()] = null;

            return;
        }
        $result = [];
        foreach ($attributeData as $datum) {
            $parts = [];
            foreach ($datum as $datumKey => $datumValue) {
                $datumValue = $this->encodeSymbols($datumValue);
                $parts[]    = $datumKey . '=' . $datumValue . '';
            }
            $result[] = implode(',', $parts);
        }
        $row[$prefix . $this->getName()] = $result ? implode('|', $result) : null;
    }

    /**
     * Collect data for magento2 product import
     *
     * @param array $data
     * @return array|null
     */
    public function collectImportDataMageTwo($data)
    {
        if (!$this->hasOwnTable()) {
            return null;
        }

        if (!isset($data['custom_option_row_' . $this->getName()])) {
            return null;
        }

        $this->entity = $this->dataObjectFactory->create();
        $this->entity->setType('product');

        $images       = [];
        $preparedData = [];
        $iterator     = 0;

        $attributeData = $data['custom_option_row_' . $this->getName()];
        if (empty($attributeData)) {
            return $this->collectImages($images);
        }

        $step1 = explode('|', $attributeData);
        foreach ($step1 as $step1Item) {
            $step2 = explode(',', $step1Item);
            foreach ($step2 as $step2Item) {
                $step3Item                              = explode('=', $step2Item);
                $step3Item[1]                           = $this->decodeSymbols($step3Item[1]);
                $preparedData[$iterator][$step3Item[0]] = $step3Item[1];
            }
            $iterator++;
        }

        $images[$data['custom_option_row_id']] = $preparedData;

        return $this->collectImages($images);
    }

    /**
     * Get value description data
     *
     * @param array $optionTypeIds
     * @return array
     */
    public function getValueAttributesData(array $optionTypeIds): array
    {
        $connection             = $this->resource->getConnection();
        $optionTypeImages = $connection->select()->from(
            $this->resource->getTableName(ImageModel::TABLE_NAME),
        )->where('option_type_id IN (?)', $optionTypeIds);

        return $this->transformToJson($optionTypeImages->query()->fetchAll());
    }

    /**
     * Transform images data to json like:
     * [{\"value\":\"/p/h/photo.jpeg\",\"option_type_image_id\":\"744\",\"title_text\":\"\",
     * \"sort_order\":\"1\",\"base_image\":\"1\",\"replace_main_gallery_image\":\"1\",\"custom_media_type\":\"image\",
     * \"color\":\"\",\"disabled\":\"0\",\"overlay_image\":\"0\",\"tooltip_image\":\"1\"}]
     *
     * @param array $optionTypeImagesData
     * @return array
     */
    protected function transformToJson(array $optionTypeImagesData): array
    {
        $imagesData = [];
        foreach ($optionTypeImagesData as $item) {
            $imagesData[$item['option_type_id']][] =
                [
                    ImageModel::COLUMN_VALUE  => $item['value'],
                    ImageModel::COLUMN_OPTION_TYPE_IMAGE_ID => $item['option_type_image_id'],
                    ImageModel::COLUMN_TITLE_TEXT => $item['title_text'],
                    ImageModel::COLUMN_SORT_ORDER => $item['sort_order'],
                    ImageModel::COLUMN_BASE_IMAGE => $item['base_image'],
                    ImageModel::COLUMN_REPLACE_MAIN_GALLERY_IMAGE => $item['replace_main_gallery_image'],
                    'custom_media_type' => $item['media_type'],
                    ImageModel::COLUMN_COLOR => $item['color'],
                    ImageModel::COLUMN_HIDE_IN_GALLERY => $item['disabled'],
                    ImageModel::COLUMN_OVERLAY_IMAGE => $item['overlay_image'],
                    ImageModel::COLUMN_TOOLTIP_IMAGE => $item['tooltip_image'],
                ];
        }
        $result = [];

        foreach ($imagesData as $key => $image) {
            $result[$key] = $this->serializer->serialize($image);
        }
        return $result;
    }
}
