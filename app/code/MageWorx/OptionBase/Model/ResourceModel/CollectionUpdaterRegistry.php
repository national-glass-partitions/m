<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionBase\Model\ResourceModel;

class CollectionUpdaterRegistry
{
    /**
     * Current product or group type
     *
     * @var string
     */
    protected string $currentEntityType;

    /**
     * Current product/group Ids
     *
     * @var array
     */
    protected array $currentEntityIds;

    /**
     * Current product row ids (actual only if Magento EE)
     *
     * @var array
     */
    protected array $currentRowIds = [];

    /**
     * Array of product/group option's IDs
     *
     * @var array
     */
    protected array $optionIds;

    /**
     * Array of product/group option value's IDs
     *
     * @var array
     */
    protected array $optionValueIds;

    /**
     * Is applied group concat variable flag
     *
     * @var bool
     */
    protected bool $isAppliedGroupConcat = false;

    /**
     * @param string $currentEntityType
     * @param array $currentEntityIds
     * @param array $optionIds
     * @param array $optionValueIds
     */
    public function __construct(
        $currentEntityType = '',
        $currentEntityIds = [],
        $optionIds = [],
        $optionValueIds = []
    ) {
        $this->currentEntityType = $currentEntityType;
        $this->currentEntityIds  = $currentEntityIds;
        $this->optionIds         = $optionIds;
        $this->optionValueIds    = $optionValueIds;
    }

    /**
     * Set current product or group entity id
     *
     * @param array $entityIds
     */
    public function setCurrentEntityIds($entityIds)
    {
        $this->currentEntityIds = $entityIds;
    }

    /**
     * Get current product or group entity id
     *
     * @return array
     */
    public function getCurrentEntityIds()
    {
        return $this->currentEntityIds;
    }

    /**
     * Set current product row id
     *
     * @param array $entityIds
     */
    public function setCurrentRowIds($entityIds)
    {
        $this->currentRowIds = $entityIds;
    }

    /**
     * Get current product row id
     *
     * @return array
     */
    public function getCurrentRowIds()
    {
        return $this->currentRowIds;
    }

    /**
     * Set current product or group entity type
     *
     * @param string $entityType
     */
    public function setCurrentEntityType($entityType)
    {
        $this->currentEntityType = $entityType;
    }

    /**
     * Get current product or group entity type
     *
     * @return string
     */
    public function getCurrentEntityType()
    {
        return $this->currentEntityType;
    }

    /**
     * Set array of product/group option's IDs
     *
     * @param array $optionIds
     */
    public function setOptionIds($optionIds)
    {
        $this->optionIds = $optionIds;
    }

    /**
     * Get array of product/group option's IDs
     *
     * @return array
     */
    public function getOptionIds()
    {
        return $this->optionIds;
    }

    /**
     * Set array of product/group option value's IDs
     *
     * @param array $optionValueIds
     */
    public function setOptionValueIds($optionValueIds)
    {
        $this->optionValueIds = $optionValueIds;
    }

    /**
     * Get array of product/group option value's IDs
     *
     * @return array
     */
    public function getOptionValueIds()
    {
        return $this->optionValueIds;
    }

    /**
     * Set isAppliedGroupConcat flag
     *
     * @param bool $value
     */
    public function setIsAppliedGroupConcat($value)
    {
        $this->isAppliedGroupConcat = (bool)$value;
    }

    /**
     * Get isAppliedGroupConcat flag
     *
     * @return bool
     */
    public function getIsAppliedGroupConcat()
    {
        return $this->isAppliedGroupConcat;
    }
}
