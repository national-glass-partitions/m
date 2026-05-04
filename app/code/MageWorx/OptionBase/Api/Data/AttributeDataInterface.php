<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionBase\Api\Data;

/**
 * Class Attributes
 */
interface AttributeDataInterface
{
    /**
     * Prepare attribute data to array
     *
     */
    public function toArray(): array;

    /**
     * Get attribute data
     *
     */
    public function getData(?string $key);
}
