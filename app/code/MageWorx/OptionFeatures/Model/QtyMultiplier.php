<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionFeatures\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductOptionInterface;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionFeatures\Helper\Data as Helper;
use MageWorx\OptionBase\Helper\System as SystemHelper;
use Magento\Framework\Locale\FormatInterface;

class QtyMultiplier
{
    protected Helper $helper;
    protected BaseHelper $baseHelper;
    protected array $buyRequest;
    protected SystemHelper $systemHelper;
    protected FormatInterface $localeFormat;

    public function __construct(
        Helper $helper,
        BaseHelper $baseHelper,
        SystemHelper $systemHelper,
        FormatInterface $localeFormat
    ) {
        $this->helper       = $helper;
        $this->baseHelper   = $baseHelper;
        $this->systemHelper = $systemHelper;
        $this->localeFormat = $localeFormat;
    }

    /**
     * Process buy request options to calculate qty_multiplier total qty
     *
     * @param array $options
     * @param array $buyRequest
     * @param ProductInterface $quoteProduct
     * @return int|float
     */
    public function getTotalQtyMultiplierQuantity(array $options, $buyRequest, $quoteProduct)
    {
        $qtyMultiplierTotalQty = 0;

        if (!$options || !is_array($options)) {
            return $qtyMultiplierTotalQty;
        }

        $this->buyRequest = $buyRequest;
        $productQty       = (string)$this->localeFormat->getNumber($buyRequest['qty'] ?? 1);

        foreach ($options as $optionId => $values) {
            $option = $quoteProduct->getOptionById($optionId);
            if (!$option) {
                continue;
            }

            if (in_array($option->getType(), $this->baseHelper->getSelectableOptionTypes())) {
                $qtyMultiplierTotalQty += $this->getOptionQtyMultiplierQuantity(
                    $option,
                    $optionId,
                    $values,
                    $productQty
                );
            }
        }

        return $qtyMultiplierTotalQty;
    }

    /**
     * Get total qty_multiplier's qty from selected option values
     *
     * @param ProductOptionInterface|\Magento\Catalog\Model\Product\Option $option
     * @param int $optionId
     * @param array|string $values
     * @param float|int $productQty
     * @return float|int
     */
    protected function getOptionQtyMultiplierQuantity($option, $optionId, $values, $productQty)
    {
        $totalQty      = 0;
        $optionTypeIds = is_array($values) ? $values : explode(',', $values);
        $isOneTime     = $option->getOneTime();

        foreach ($optionTypeIds as $index => $optionTypeId) {
            if (!$optionTypeId) {
                continue;
            }
            $value = $option->getValueById($optionTypeId);

            if (!$value) {
                continue;
            }

            $qtyMultiplier = $value->getQtyMultiplier();

            $optionQty = $this->helper->getOptionQty($this->buyRequest, $optionId, $optionTypeId);
            $totalQty  += $isOneTime
                ? $optionQty * $qtyMultiplier
                : $optionQty * $qtyMultiplier * $productQty;
        }

        return $totalQty;
    }

    /**
     * Calculated qtyMultiplierQty for current item qty
     *
     * @param $orderItem
     * @param $currentQty
     * @return float|int
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getQtyMultiplierQtyForCurrentItemQty(
        \Magento\Sales\Model\Order\Item $orderItem,
        float $currentQty
    ): float {
        return $this->getQtyMultiplierFromOrderItem($orderItem) * $currentQty;
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @return float
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getQtyMultiplierFromOrderItem(\Magento\Sales\Model\Order\Item $orderItem): float
    {
        // This code was added as quick fix for merge mainline
        // https://github.com/magento-engcom/msi/issues/1586
        if (null === $orderItem) {
            return 0;
        }

        $buyRequest = $orderItem->getBuyRequest();
        if (!$buyRequest->getOptions()) {
            return 0;
        }

        if ($this->baseHelper->checkModuleVersion('101.2.2', '', '>=', '', 'Magento_Quote') &&
            !$this->systemHelper->isAdmin()
        ) {
            $qtyMultiplierQty = $buyRequest->getQtyMultiplierQty();
            $originalQty      = $buyRequest->getOriginalQty();
        } else {
            /* Using for magento lower than 2.4.2 because magento rewrite quote item buy_request */
            $qtyMultiplierQty = $this->getTotalQtyMultiplierQuantity(
                $buyRequest->getOptions(),
                $buyRequest->toArray(),
                $orderItem->getProduct()
            );
            $originalQty      = $buyRequest->getData('qty');
        }

        if (!$qtyMultiplierQty) {
            return 0;
        }

        return $qtyMultiplierQty / (float)$originalQty;
    }
}
