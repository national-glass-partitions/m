<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionFeatures\Plugin;

use \Magento\Catalog\Block\Product\View;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionFeatures\Helper\Data as Helper;
use Magento\Catalog\Api\Data\ProductInterface;

class AddAdditionalProductPriceField
{
    protected Helper $helper;
    protected BaseHelper $baseHelper;

    public function __construct(
        Helper $helper,
        BaseHelper $baseHelper
    ) {
        $this->helper = $helper;
        $this->baseHelper = $baseHelper;
    }

    /**
     * Add additional product price field
     *
     * @param View $subject
     * @param string $template
     * @return string
     */
    public function beforeSetTemplate($subject, $template): string
    {
        $blockName = $subject->getNameInLayout();
        if ($blockName !== 'product.info.addtocart.additional' || !$subject->getProduct()) {
            return (string)$template;
        }

        // checkout_cart_configure - using for edit product configuration from checkout/cart
        if ($this->baseHelper->getFullActionName() == 'checkout_cart_configure') {
            return $template;
        }

        if ($this->isNeedToShowAdditionalField($subject->getProduct())) {
            $subject->setData('show_additional_price', true);
            $template = "MageWorx_OptionFeatures::catalog/product/addtocart.phtml";
        }
        if ($this->isNeedToShowShareableLink($subject->getProduct())) {
            $subject->setData('show_shareable_link', true);
            $subject->setData(
                'shareable_link_text',
                $this->helper->getShareableLinkText($subject->getProduct()->getStoreId())
            );
            $subject->setData(
                'shareable_link_success_text',
                $this->helper->getShareableLinkSuccessText($subject->getProduct()->getStoreId())
            );
            $subject->setData(
                'shareable_link_hint_text',
                $this->helper->getShareableLinkHintText($subject->getProduct()->getStoreId())
            );
            $template = "MageWorx_OptionFeatures::catalog/product/addtocart.phtml";
        }

        return (string)$template;
    }

    /**
     * Show additional field
     *
     * @param ProductInterface $product
     * @return bool
     */
    protected function isNeedToShowAdditionalField($product)
    {
        return $this->isNotBundle($product)
            && $this->isNotConfigurable($product)
            && $this->helper->isEnabledAdditionalProductPriceField($product->getStoreId())
            && $this->canShowAdditionalProductPriceField($product)
            && $product->getTypeInstance()->hasOptions($product);
    }

    /**
     * Show shareable link
     *
     * @param ProductInterface $product
     * @return bool
     */
    protected function isNeedToShowShareableLink($product)
    {
        return ($this->isShareableLinkEnabledOnProduct($product) || $this->isShareableLinkEnabledInConfig($product))
            && $product->getTypeInstance()->hasOptions($product);
    }

    /**
     * Is ShareableLink enabled on product
     *
     * @param ProductInterface $product
     * @return bool
     */
    protected function isShareableLinkEnabledOnProduct($product)
    {
        return $product->getData(Helper::KEY_SHAREABLE_LINK) === Helper::SHAREABLE_LINK_ENABLED;
    }

    /**
     * Is ShareableLink enabled in config
     *
     * @param ProductInterface $product
     * @return bool
     */
    protected function isShareableLinkEnabledInConfig($product)
    {
        return $product->getData(Helper::KEY_SHAREABLE_LINK) === Helper::SHAREABLE_LINK_USE_CONFIG
            && $this->helper->isEnabledShareableLink($product->getStoreId());
    }

    /**
     * Check if product is configurable
     *
     * @param ProductInterface $product
     * @return bool
     */
    protected function isNotConfigurable($product)
    {
        return $product->getTypeId() !== 'configurable';
    }

    /**
     * Check if product is bundle
     *
     * @param ProductInterface $product
     * @return bool
     */
    protected function isNotBundle($product)
    {
        return $product->getTypeId() !== 'bundle';
    }

    /**
     * Check if additional product field should be shown for a product
     *
     * @param ProductInterface $product
     * @return bool
     */
    protected function canShowAdditionalProductPriceField($product)
    {
        return !$product->getHideAdditionalProductPrice();
    }
}
