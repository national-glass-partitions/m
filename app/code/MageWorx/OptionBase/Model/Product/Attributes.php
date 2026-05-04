<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionBase\Model\Product;

use MageWorx\OptionBase\Api\Data\AttributeDataInterface;
/**
 * Class Attributes
 * @package MageWorx\OptionBase\Model\Option
 */
class Attributes implements AttributeDataInterface
{
    private array $data;

    /**
     * Attributes constructor.
     * @param array $data
     */
    public function __construct(
        $data = []
    ) {
        $this->data = $data;
    }

    /**
     * Prepare attribute data to array
     *
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Get attribute data
     *
     */
    public function getData(?string $key = null)
    {
        if ($key === null) {
            return $this->data;
        }

        return $this->data[$key] ?? null;
    }
}
