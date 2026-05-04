<?php
/**
 * Copyright © 2017 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionBase\Model\Entity;

class Group extends Base
{
    protected string $entityType = 'group';

    /**
     * @return string
     */
    public function getType()
    {
        return $this->entityType;
    }

    /**
     * Get group id
     * @return string
     */
    public function getDataObjectId()
    {
        return $this->getDataObject()->getGroupId();
    }

    /**
     * Get group field name
     * @return string
     */
    public function getDataObjectIdName()
    {
        return 'group_id';
    }

    /**
     * @inherit
     */
    protected function getOptionId($option)
    {
        return '';
    }

    /**
     * @inherit
     */
    protected function getOptionTypeId($value)
    {
        return '';
    }
}
