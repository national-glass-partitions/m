<?php
/**
 * Copyright © 2017 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionFeatures\Block\Adminhtml;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\Form;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json as Serializer;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Context;
use Magento\Store\Model\StoreManagerInterface;
use MageWorx\OptionFeatures\Helper\Data as Helper;
use MageWorx\OptionFeatures\Model\Image;

class Gallery extends AbstractBlock
{
    /**
     * Gallery field name suffix
     *
     * @var string
     */
    protected string $fieldNameSuffix = 'product';

    /**
     * Gallery html id
     *
     * @var string
     */
    protected string $htmlId = 'optionfeatures_media_gallery';

    /**
     * Gallery name
     *
     * @var string
     */
    protected string $name = 'optionfeatures[media_gallery]';

    /**
     * Html id for data scope
     *
     * @var string
     */
    protected string $image = 'image';
    protected string $formName = 'product_form';
    protected StoreManagerInterface $storeManager;
    protected Form $form;
    protected Registry $registry;
    protected Image $imageFactory;
    protected array $mediaAttributes = [];
    protected Helper $helper;
    protected array $imagesData = [];
    protected ResourceConnection $resource;
    protected Serializer $serializer;

    /**
     * @return string
     */
    public function getName()
    {
        return (string)$this->name;
    }

    /**
     * @param ResourceConnection $resource
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param Registry $registry
     * @param Form $form
     * @param Image $imageFactory
     * @param Helper $helper
     * @param Serializer $serializer
     * @param array $data
     */
    public function __construct(
        ResourceConnection $resource,
        Context $context,
        StoreManagerInterface $storeManager,
        Registry $registry,
        Form $form,
        Image $imageFactory,
        Helper $helper,
        Serializer $serializer,
        $data = []
    ) {
        $this->resource     = $resource;
        $this->storeManager = $storeManager;
        $this->registry     = $registry;
        $this->form         = $form;
        $this->imageFactory = $imageFactory;
        $this->helper       = $helper;
        $this->serializer   = $serializer;
        parent::__construct($context, $data);
    }

    /**
     * @return string
     */
    public function getFieldNameSuffix()
    {
        return (string)$this->fieldNameSuffix;
    }

    /**
     * @return string
     */
    public function getDataScopeHtmlId()
    {
        return (string)$this->image;
    }

    /**
     * Retrieve data object related with form
     *
     * @return ProductInterface|Product
     */
    public function getDataObject()
    {
        return $this->registry->registry('current_product');
    }

    /**
     * @return string
     */
    public function toHtml()
    {
        return (string)$this->getContentHtml();
    }

    /**
     * Prepares content block
     *
     * @return string
     */
    public function getContentHtml()
    {
        /* @var $content \MageWorx\OptionFeatures\Block\Adminhtml\Gallery\Content */
        $this->getLayout()
             ->addBlock('MageWorx\OptionFeatures\Block\Adminhtml\Gallery\Content', 'gallery_content')
             ->setTemplate('MageWorx_OptionFeatures::catalog/product/helper/gallery.phtml');
        $content = $this->getLayout()->getBlock('gallery_content');

        $this->getImages();

        $content->setId($this->getHtmlId() . '_content')->setElement($this);
        $content->setFormName($this->formName);
        $content->setAttributes($this->mediaAttributes);
        $content->setImagesData($this->imagesData);
        $galleryJs = $content->getJsObjectName();
        $content->getUploader()->getConfig()->setMegiaGallery($galleryJs);

        return (string)$content->toHtml();
    }

    /**
     * Get option value images
     */
    protected function getImages()
    {
        $data         = [];
        $optionTypeId = $this->getRequest()->getParam('option_type_id');
        $this->initMediaAttributes();
        $post = $this->getRequest()->getParam('data') ? $this->getRequest()->getParam('data') : [];
        if ($post && strpos($post, 'optionfeatures') !== false) {
            $postData = [];
            parse_str($post, $postData);
            foreach ($postData['optionfeatures']['media_gallery']['images'] as $postItem) {
                if ($postItem['removed'] == true) {
                    continue;
                }
                $data['images'][] = $postItem;
            }
            foreach ($this->helper->getImageAttributes() as $attributeCode => $attributeLabel) {
                if (isset($postData[$attributeCode])) {
                    $this->setMediaAttributeValue($attributeCode, $postData[$attributeCode]);
                }
            }
        } elseif ($post) {
            $images = $this->serializer->unserialize($post);

            if (!is_array($images)) {
                $this->imagesData = $data;

                return;
            }

            foreach ($images as $image) {
                if (!empty($image['removed'])) {
                    continue;
                }
                $data['images'][] = [
                    'value_id'          => $image[Image::COLUMN_OPTION_TYPE_IMAGE_ID],
                    'custom_media_type' => $image['custom_media_type'],
                    'file'              => $image[Image::COLUMN_VALUE],
                    'color'             => $image[Image::COLUMN_COLOR],
                    'label'             => htmlspecialchars_decode($image[Image::COLUMN_TITLE_TEXT]),
                    'position'          => $image[Image::COLUMN_SORT_ORDER],
                    'disabled'          => $image[Image::COLUMN_HIDE_IN_GALLERY]
                ];
                foreach ($this->helper->getImageAttributes() as $attributeCode => $attributeLabel) {
                    if (!empty($image[$attributeCode])) {
                        $this->setMediaAttributeValue($attributeCode, $image['value']);
                    }
                }
            }
        } else {
            if ($this->getRequest()->getParam('form_name') == 'mageworx_optiontemplates_group_form') {
                $connection = $this->resource->getConnection();
                $select     = $connection->select()
                                         ->from($this->resource->getTableName(Image::OPTIONTEMPLATES_TABLE_NAME))
                                         ->where(Image::COLUMN_OPTION_TYPE_ID . ' = "' . $optionTypeId . '"');
                $imageItems = $connection->fetchAll($select);

                foreach ($imageItems as $item) {
                    $data['images'][$item['option_type_image_id']] = [
                        'value_id'                    => $item['option_type_image_id'],
                        'position'                    => $item['sort_order'],
                        'file'                        => $item['value'],
                        'label'                       => $item['title_text'],
                        'custom_media_type'           => $item['media_type'],
                        'color'                       => $item['color'],
                        Image::COLUMN_HIDE_IN_GALLERY => $item[Image::COLUMN_HIDE_IN_GALLERY],
                    ];
                    foreach ($this->helper->getImageAttributes() as $attributeCode => $attributeLabel) {
                        if ($item[$attributeCode]) {
                            $this->setMediaAttributeValue($attributeCode, $item['value']);
                        }
                    }
                }
            } elseif ($this->getRequest()->getParam('form_name') == 'product_form') {
                $collection = $this->imageFactory->getCollection();
                $collection->addFieldToFilter('option_type_id', $optionTypeId);

                foreach ($collection->getItems() as $collectionItem) {
                    $data['images'][$collectionItem->getOptionTypeImageId()] = [
                        'value_id'                    => $collectionItem->getOptionTypeImageId(),
                        'position'                    => $collectionItem->getSortOrder(),
                        'file'                        => $collectionItem->getValue(),
                        'label'                       => $collectionItem->getTitleText(),
                        'custom_media_type'           => $collectionItem->getMediaType(),
                        'color'                       => $collectionItem->getColor(),
                        Image::COLUMN_HIDE_IN_GALLERY => $collectionItem->getData(Image::COLUMN_HIDE_IN_GALLERY),
                    ];
                    foreach ($this->helper->getImageAttributes() as $attributeCode => $attributeLabel) {
                        if ($collectionItem->getData($attributeCode)) {
                            $this->setMediaAttributeValue($attributeCode, $collectionItem->getValue());
                        }
                    }
                }
            }
        }

        $this->imagesData = $data;
    }

    /**
     * Init media attributes
     */
    protected function initMediaAttributes()
    {
        foreach ($this->helper->getImageAttributes() as $attributeCode => $attributeLabel) {
            $this->mediaAttributes[$attributeCode] = [
                'code'  => $attributeCode,
                'label' => $attributeLabel,
                'value' => '',
            ];
        }
    }

    /**
     * Set media attribute value
     *
     * @param $code
     * @param $value
     */
    protected function setMediaAttributeValue($code, $value)
    {
        $this->mediaAttributes[$code]['value'] = $value;
    }

    /**
     * @return string
     */
    protected function getHtmlId()
    {
        return (string)$this->htmlId;
    }
}
