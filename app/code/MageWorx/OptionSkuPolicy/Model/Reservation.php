<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionSkuPolicy\Model;

use Magento\InventorySalesApi\Api\Data\ProductSalableResultInterface;

class Reservation
{
    protected array $reservations;

    /**
     * @param string $sku
     * @param ProductSalableResultInterface $result
     */
    public function setIsSalableWithReservationsCondition($sku, $result)
    {
        $this->reservations[$sku] = $result;
    }

    /**
     * @param string $sku
     * @return float|null
     */
    public function getIsSalableWithReservationsCondition($sku)
    {
        return isset($this->reservations[$sku]) ? $this->reservations[$sku] : null;
    }
}
