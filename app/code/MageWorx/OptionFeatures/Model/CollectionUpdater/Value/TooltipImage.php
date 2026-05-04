<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionFeatures\Model\CollectionUpdater\Value;

use MageWorx\OptionBase\Model\Product\Option\AbstractUpdater;
use MageWorx\OptionFeatures\Model\Image;

class TooltipImage extends AbstractUpdater
{
    /**
     * {@inheritdoc}
     */
    public function getFromConditions(array $conditions)
    {
        return [$this->getTableAlias() => $this->getTableName($conditions['entity_type'])];
    }

    /**
     * {@inheritdoc}
     */
    public function getTableName($entityType)
    {
        if ($entityType == 'group') {
            return $this->resource->getTableName(Image::OPTIONTEMPLATES_TABLE_NAME);
        }
        return $this->resource->getTableName(Image::TABLE_NAME);
    }

    /**
     * {@inheritdoc}
     */
    public function getOnConditionsAsString()
    {
        $onConditions = 'main_table.option_type_id = ' . $this->getTableAlias() . '.option_type_id';
        $onConditions .= ' AND ' . $this->getTableAlias() . '.tooltip_image = "1"';
        return $onConditions;
    }

    /**
     * {@inheritdoc}
     */
    public function getColumns()
    {
        return [
            'tooltip_image' => $this->getTableAlias() . '.value',
            'tooltip_image_type' => $this->getTableAlias() . '.media_type'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTableAlias()
    {
        return 'option_value_tooltip_image';
    }
}
