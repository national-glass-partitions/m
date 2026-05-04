<?php
/**
 * Copyright © 2017 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionFeatures\Block\Adminhtml\Gallery;

use Magento\Backend\Block\Media\Uploader;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\View\Element\AbstractBlock;
use MageWorx\OptionFeatures\Model\Image;
use MageWorx\OptionFeatures\Model\Product\Option\Value\Media\Config;

class Content extends Widget
{
    /**
     * @var string
     */
    protected $_template = 'catalog/product/helper/gallery.phtml';

    protected Config $mediaConfig;
    protected EncoderInterface $jsonEncoder;
    protected Image $imageFactory;
    private ImageHelper $imageHelper;

    public function __construct(
        Context $context,
        EncoderInterface $jsonEncoder,
        Config $mediaConfig,
        ImageHelper $imageHelper,
        array $data = []
    ) {
        $this->jsonEncoder = $jsonEncoder;
        $this->mediaConfig = $mediaConfig;
        $this->imageHelper = $imageHelper;
        parent::__construct($context, $data);
    }

    /**
     * @return string
     */
    public function getUploaderUrl()
    {
        return (string)$this->_urlBuilder->getUrl('mageworx_optionfeatures/option_value_gallery/upload');
    }

    /**
     * Retrieve uploader block html
     *
     * @return string
     */
    public function getUploaderHtml()
    {
        return (string)$this->getChildHtml('uploader');
    }

    /**
     * @return string
     */
    public function getAddImagesButton()
    {
        return (string)$this->getButtonHtml(
            __('Add New Images'),
            $this->getJsObjectName() . '.showUploader()',
            'add',
            $this->getHtmlId() . '_add_images_button'
        );
    }

    /**
     * @return string
     */
    public function getJsObjectName()
    {
        return $this->getHtmlId() . 'JsObject';
    }

    /**
     * @return string
     */
    public function getImagesJson()
    {
        $value = $this->getImagesData();
        if (is_array($value) &&
            array_key_exists('images', $value) &&
            is_array($value['images']) &&
            count($value['images'])
        ) {
            $mediaDir = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA);
            $images = $this->sortImagesByPosition($value['images']);
            foreach ($images as &$image) {
                $image['url'] = $this->mediaConfig->getMediaUrl($image['file']);
                try {
                    $fileHandler = $mediaDir->stat($this->mediaConfig->getMediaPath($image['file']));
                    $image['size'] = $fileHandler['size'];
                } catch (FileSystemException $e) {
                    $image['url'] = $this->imageHelper->getDefaultPlaceholderUrl('small_image');
                    $image['size'] = 0;
                    $this->_logger->warning($e);
                }
            }

            return (string)$this->jsonEncoder->encode($images);
        }

        return '[]';
    }

    /**
     * Sort images array by position key
     *
     * @param array $images
     * @return array
     */
    private function sortImagesByPosition($images)
    {
        if (is_array($images)) {
            usort($images, function ($imageA, $imageB): int {
                return ($imageA['position'] < $imageB['position']) ? -1 : 1;
            });
        }

        return $images;
    }

    /**
     * Get image types data
     *
     * @return array
     */
    public function getImageTypes()
    {
        $imageTypes = [];
        foreach ($this->getMediaAttributes() as $attribute) {
            $imageTypes[$attribute['code']] = [
                'code' => $attribute['code'],
                'value' => $attribute['value'],
                'label' => $attribute['label'],
                'scope' => $attribute['scope'],
                'name' => $attribute['name'],
            ];
        }

        return $imageTypes;
    }

    /**
     * Retrieve media attributes
     *
     * @return array
     */
    public function getMediaAttributes()
    {
        $values = [];
        foreach ($this->getAttributes() as $attribute) {
            $values[] = [
                'code' => $attribute['code'],
                'value' => $attribute['value'],
                'label' => $attribute['label'],
                'scope' => '',
                'name' => $attribute['code'],
            ];
        }

        return $values;
    }

    /**
     * @return AbstractBlock
     */
    protected function _prepareLayout()
    {
        $this->addChild(
            'uploader',
            \Magento\Backend\Block\Media\Uploader::class,
            ['image_upload_config_data' => $this]
        );

        $this->getUploader()->getConfig()->setUrl(
            $this->_urlBuilder->getUrl('mageworx_optionfeatures/option_value_gallery/upload')
        )->setFileField(
            'image'
        )->setFilters(
            [
                'images' => [
                    'label' => __('Images (.gif, .jpg, .png)'),
                    'files' => ['*.gif', '*.jpg', '*.jpeg', '*.png'],
                ],
            ]
        );

        return parent::_prepareLayout();
    }

    /**
     * Get image resize configuration
     *
     * @return int
     */
    public function getIsResizeEnabled()
    {
        return 1;
    }

    /**
     * Retrieve uploader block
     *
     * @return Uploader
     */
    public function getUploader()
    {
        return $this->getChildBlock('uploader');
    }
}
