<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionBase\Model\Product\Option\Value;

class CollectionUpdaters
{
    private array $data = [];

    /**
     * Excluded value attributes from the main collection for performance reasons
     *
     * @var array|string[] $attributesToExclude
     */
    protected array $attributesToExclude = [
        'mageworx_title',
        'mageworx_option_type_price',
        'option_type_description',
        'option_value_images'
    ];

    /**
     * @param array $data
     */
    public function __construct(
        $data = []
    ) {
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->data;
    }

    /**
     * @param null $key
     * @return mixed|null
     */
    public function getData($key = null)
    {
        if (!$key) {
            return $this->data;
        }

        return isset($this->data[$key]) ? $this->data[$key] : null;
    }

    /**
     * Get excluded attributes
     */
    public function getAttributesToExclude(): array
    {
        return $this->attributesToExclude;
    }
}
