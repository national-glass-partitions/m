<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionBase\Model;

use MageWorx\OptionBase\Api\ValidatorInterface;

class ValidationResolver
{
    private array $validators = [];

    /**
     * @param array $validators
     */
    public function __construct(
        $validators = []
    ) {
        $this->validators = $validators;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->validators;
    }

    /**
     * @param string|null $key
     * @return ValidatorInterface|ValidatorInterface[]|array
     */
    public function getValidators($key = null)
    {
        if (!$key) {
            return $this->validators;
        }

        return isset($this->validators[$key]) ? $this->validators[$key] : null;
    }
}
