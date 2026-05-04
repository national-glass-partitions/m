<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionTemplates\Model\Group\Source;

class SystemAttributes
{

    protected $data = [];

    /**
     * SystemAttributes constructor.
     * @param array $data
     */
    public function __construct(
        $data = []
    ) {
        $this->data = $data;
    }

    public function toArray()
    {
        return $this->data;
    }
}
