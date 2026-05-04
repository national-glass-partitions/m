<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionAdvancedPricing\Model;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class ConditionValidator
{
    protected TimezoneInterface $timezone;
    protected array $priceItem;
    protected float $valuePrice;
    protected int $timestamp;

    public function __construct(
        TimezoneInterface $timezone
    ) {
        $this->timezone = $timezone;
    }

    /**
     * Validate date_from, date_to and price conditions for tier/special price item
     *
     * @param array $priceItem
     * @param float $valuePrice
     * @return bool
     */
    public function isValidated($priceItem, $valuePrice)
    {
        $this->priceItem  = $priceItem;
        $this->valuePrice = $valuePrice;
        $currentDate      = $this->timezone->date();
        $this->timestamp  = $currentDate->getTimestamp();

        if (!$this->isValidDateFrom()) {
            return false;
        }

        if (!$this->isValidDateTo()) {
            return false;
        }

        if (!$this->isValidPrice()) {
            return false;
        }
        return true;
    }

    /**
     * Validate date_from condition
     *
     * @return bool
     */
    protected function isValidDateFrom()
    {
        if ($this->priceItem['date_from'] !== ''
            && strtotime($this->priceItem['date_from']) > $this->timestamp
        ) {
            return false;
        }
        return true;
    }

    /**
     * Validate date_to condition
     *
     * @return bool
     */
    protected function isValidDateTo()
    {
        if ($this->priceItem['date_to'] !== ''
            && strtotime($this->priceItem['date_to']) + 86400 < $this->timestamp
        ) {
            return false;
        }
        return true;
    }

    /**
     * Validate price condition
     *
     * @return bool
     */
    protected function isValidPrice()
    {
        return $this->priceItem['price'] < $this->valuePrice;
    }
}
