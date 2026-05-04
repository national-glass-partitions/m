<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionFeatures\Plugin;

use Magento\Framework\App\Area;
use Magento\Quote\Model\Quote\Item;
use MageWorx\OptionFeatures\Helper\Data as Helper;
use MageWorx\OptionBase\Helper\Data as BaseHelper;

class AroundCompareOptions
{
    protected Helper $helper;
    protected BaseHelper $baseHelper;
    protected \Magento\Framework\App\State $state;

    /**
     * AroundCompareOptions constructor.
     * @param Helper $helper
     * @param BaseHelper $baseHelper
     * @param \Magento\Framework\App\State $state
     */
    public function __construct(
        Helper $helper,
        BaseHelper $baseHelper,
        \Magento\Framework\App\State $state
    ) {
        $this->helper     = $helper;
        $this->baseHelper = $baseHelper;
        $this->state      = $state;
    }

    /**
     * Check if two options array are identical
     * First options array is prerogative
     * Second options array checked against first one
     *
     * @param Item $subject
     * @param \Closure $proceed
     * @param $options1
     * @param $options2
     * @return bool|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function aroundCompareOptions(Item $subject, \Closure $proceed, $options1, $options2)
    {
        if (!$this->helper->isQtyInputEnabled()) {
            return $proceed($options1, $options2);
        }

        foreach ($options1 as $option) {
            $code = $option->getCode();
            if (in_array($code, ['info_buyRequest'])) {
                try {
                    $buyRequestValue1 = $this->baseHelper->jsonDecode($option->getValue());
                    $buyRequestValue2 = '';
                    if (isset($options2[$code])) {
                        $buyRequestValue2 = $this->baseHelper->jsonDecode($options2[$code]->getValue());
                    }
                } catch (\Exception $e) {
                    return false;
                }

                if ($this->validateRequestValues($buyRequestValue1)
                    && count($buyRequestValue1) === 1
                ) {
                    continue;
                }

                //skip checking info_buyRequest for sku policy
                if (!empty($buyRequestValue1['sku_policy_sku'])
                    || !empty($buyRequestValue2['sku_policy_sku']))
                {
                    continue;
                }

                //skip checking info_buyRequest if product are the same (product qty not comparing)
                if ($this->validateRequestValues($buyRequestValue1)
                    && $this->validateRequestValues($buyRequestValue2)
                ) {
                    if ($this->modifyBuyRequestValues($buyRequestValue1)
                        == $this->modifyBuyRequestValues($buyRequestValue2)
                    ) {
                        continue;
                    }
                }

                //skip checking info_buyRequest for compatibility with MW OrderEditor
                if ($this->state->getAreaCode() == Area::AREA_ADMINHTML && isset($options2[$code]['item_id'])) {
                    continue;
                }
            }
            if (!isset($options2[$code]) || $options2[$code]->getValue() != $option->getValue()) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param $buyRequestValue
     * @return bool
     */
    public function validateRequestValues($buyRequestValue)
    {
        return $buyRequestValue && is_array($buyRequestValue) && isset($buyRequestValue['qty']);
    }

    /**
     * @param $buyRequestValue
     * @return mixed
     */
    public function modifyBuyRequestValues($buyRequestValue)
    {
        unset($buyRequestValue['qty']);
        return $buyRequestValue;
    }
}
