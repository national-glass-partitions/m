<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionSkuPolicy\Plugin;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\InventorySales\Model\IsProductSalableForRequestedQtyCondition\IsSalableWithReservationsCondition;
use MageWorx\OptionSkuPolicy\Model\Reservation;
use MageWorx\OptionSkuPolicy\Helper\Data as Helper;

class AroundIsSalableWithReservationsCondition
{
    protected Reservation $reservation;
    protected ProductRepositoryInterface $productRepository;
    protected Helper $helper;

    public function __construct(
        Reservation $reservation,
        ProductRepositoryInterface $productRepository,
        Helper $helper
    ) {
        $this->reservation       = $reservation;
        $this->productRepository = $productRepository;
        $this->helper            = $helper;
    }

    /**
     * @param IsSalableWithReservationsCondition $subject
     * @param \Closure $proceed
     * @param string $sku
     * @param int $stockId
     * @param float $requestedQty
     * @return float
     */
    public function aroundExecute(
        IsSalableWithReservationsCondition $subject,
        \Closure $proceed,
        string $sku,
        int $stockId,
        float $requestedQty
    ) {
        if (!$this->helper->isEnabledSkuPolicy() || !$this->helper->isSkuPolicyAppliedToCartAndOrder()) {
            return $proceed($sku, $stockId, $requestedQty);
        }

        $product = $this->productRepository->get($sku);
        if (!$product->getOptions()) {
            return $proceed($sku, $stockId, $requestedQty);
        }

        $result = $this->reservation->getIsSalableWithReservationsCondition($sku);
        if (!isset($result)) {
            $this->reservation->setIsSalableWithReservationsCondition($sku, $proceed($sku, $stockId, $requestedQty));
        }
        return $this->reservation->getIsSalableWithReservationsCondition($sku);
    }
}
