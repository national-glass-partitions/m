<?php
/**
 * Scommerce LazyLoading  plugin file for images rendering
 *
 * @category   Scommerce
 * @package    Scommerce_LazyLoading
 * @author     Sommerce Mage <core@scommerce-mage.co.uk>
 */

namespace Scommerce\LazyLoading\Plugin\Block\Product;

/**
 * Class Image
 * @package Scommerce_LazyLoading
 */
class Image
{
   
    /**
     * @var $_isPreLoadImage
     */
    protected $_isPreLoadImage = null;

    /**
     * @var \Metagento\LazyLoad\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    protected $productMetadata;
    /**
     * @param \Scommerce\LazyLoading\Helper\Data $helper
     * @param \Magento\Framework\App\Request\Http $request
     */
    public function __construct(
        \Scommerce\LazyLoading\Helper\Data $helper,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata
    ) {
        $this->helper = $helper;
        $this->productMetadata = $productMetadata;
    }
    
    /**
     * Render html
     *
     * @param \Magento\Catalog\Block\Product\Image $subject
     * @param string $result
     * @return string
     */
    public function afterToHtml(\Magento\Catalog\Block\Product\Image $subject, $result)
    {
        if ($this->helper->checkExcludePages() && $this->helper->applyLazyLoad()) {
            if ($this->helper->checkImagesTag($result)) {
                return $result;
            } else {
                $find = 'img class="';
                $replace = 'img class="lazy swatch-option-loading ';
                return str_replace($find, $replace, $result);
            }
        }
        return $result;
    }

    /**
     * Render html
     *
     * @param \Magento\Catalog\Block\Product\Image $subject
     * @return string
     */
    public function beforeToHtml(\Magento\Catalog\Block\Product\Image $subject)
    {
        if ($this->isPreLoadImage()) {
            $customAttributes = [];
            if($this->getMagentoVersion() < "2.4.0"){
                $customAttributes = trim($subject->getCustomAttributes() . ' data-original="' . $subject->getImageUrl() . '"');
            }else{
                $customAttributes = array_merge(
                    $subject->getCustomAttributes(),
                    [
                        'data-original' => $subject->getImageUrl()
                    ]
                );
            }
            $subject->setCustomAttributes($customAttributes);
        }
        return [$subject];
    }

    /**
     * Checked, if image preload
     *
     * @return boolean
     */
    protected function isPreLoadImage()
    {
        if ($this->_isPreLoadImage == null) {
            $this->_isPreLoadImage = $this->helper->isPreLoadImage();
        }
        return $this->_isPreLoadImage;
    }

    /**
     * @return string
     */
    public function getMagentoVersion() {
        return $this->productMetadata->getVersion();
    }
}
