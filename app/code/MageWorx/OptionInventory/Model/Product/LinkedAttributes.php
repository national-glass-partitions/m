<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionInventory\Model\Product;


class LinkedAttributes
{
    protected array $data = [];

    /**
     * @param array $data
     */
    public function __construct(
        $data = []
    ) {
        $this->data = $data;
    }

    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * @param null $key
     * @return array|mixed
     */
    public function getData(string $key = null): ? \MageWorx\OptionLink\Helper\Attribute
    {
        if (!$key) {
            return $this->data;
        }

        return isset($this->data[$key]) ? $this->data[$key] : null;
    }
}
