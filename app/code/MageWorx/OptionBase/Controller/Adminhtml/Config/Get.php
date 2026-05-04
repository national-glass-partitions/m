<?php
/**
 * Copyright ©  MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionBase\Controller\Adminhtml\Config;

use Magento\Framework\Escaper;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\Result\Raw;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionBase\Helper\System as SystemHelper;
use MageWorx\OptionBase\Model\Config\Base as BaseConfig;
use Magento\Catalog\Api\ProductRepositoryInterface;

class Get extends Action
{
    /**
     * Raw factory
     *
     * @var RawFactory
     */
    protected RawFactory $rawFactory;

    protected BaseHelper $baseHelper;
    protected SystemHelper $systemHelper;
    protected BaseConfig $baseConfig;
    protected Escaper $escaper;
    protected ProductRepositoryInterface $productRepository;

    /**
     * Load constructor.
     *
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param BaseHelper $baseHelper
     * @param SystemHelper $systemHelper
     * @param BaseConfig $baseConfig
     * @param Escaper $escaper
     * @param ProductRepositoryInterface $productRepository
     * @param RawFactory $rawFactory
     */
    public function __construct(
        Context $context,
        BaseHelper $baseHelper,
        SystemHelper $systemHelper,
        BaseConfig $baseConfig,
        Escaper $escaper,
        ProductRepositoryInterface $productRepository,
        RawFactory $rawFactory
    ) {
        $this->rawFactory        = $rawFactory;
        $this->baseHelper        = $baseHelper;
        $this->systemHelper      = $systemHelper;
        $this->baseConfig        = $baseConfig;
        $this->escaper           = $escaper;
        $this->productRepository = $productRepository;
        return parent::__construct($context);
    }

    /**
     * Render block form
     *
     * @return Raw
     * @throws \Exception
     */
    public function execute()
    {
        $productId = $this->getRequest()->getPost('productId');
        $product = $this->productRepository->getById($productId, false, $this->systemHelper->resolveCurrentStoreId());
        $result = [
            "optionConfig"               => $this->baseConfig->getJsonConfig($product),
            "systemConfig"               => $this->baseConfig->getSystemJsonConfig('adminhtml'),
            "productConfig"              => $this->baseConfig->getProductJsonConfig($product),
            "localePriceFormat"          => $this->baseConfig->getLocalePriceFormat(),
            "productFinalPriceExclTax"   => (float)$this->baseConfig->getProductFinalPrice($product,false),
            "productRegularPriceExclTax" => (float)$this->baseConfig->getProductRegularPrice($product,false),
            "productFinalPriceInclTax"   => (float)$this->baseConfig->getProductFinalPrice($product,true),
            "productRegularPriceInclTax" => (float)$this->baseConfig->getProductRegularPrice($product,true),
            "priceDisplayMode"           => (int)$this->baseConfig->getPriceDisplayMode(),
            "catalogPriceContainsTax"    => (int)$this->baseConfig->getCatalogPriceContainsTax(),
            "extendedOptionsConfig"      => $this->baseConfig->getExtendedOptionsConfig($product),
            "productId"                  => (int)$this->baseConfig->getProductId($product)
        ];

        /** @var \Magento\Framework\Controller\Result\Raw $response */
        $response = $this->rawFactory->create();
        $response->setHeader('Content-type', 'application/json');
        $response->setContents($this->baseHelper->jsonEncode($result));

        return $response;
    }
}
