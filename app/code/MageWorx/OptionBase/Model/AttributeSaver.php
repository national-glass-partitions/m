<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionBase\Model;

use MageWorx\OptionBase\Helper\Data as Helper;
use MageWorx\OptionBase\Model\Product\Attributes as ProductAttributes;
use MageWorx\OptionBase\Model\Product\Option\Attributes as OptionAttributes;
use MageWorx\OptionBase\Model\Product\Option\Value\Attributes as OptionValueAttributes;
use Magento\Framework\App\ResourceConnection;
use MageWorx\OptionBase\Api\Data\AttributeDataInterface;

class AttributeSaver
{
    protected Helper $helper;
    protected ProductAttributes $productAttributes;
    protected OptionAttributes $optionAttributes;
    protected OptionValueAttributes $optionValueAttributes;
    protected ResourceConnection $resource;

    /**
     * Array of attribute data for multiple insert
     *
     * @var array
     */
    protected array $attributeData = [];

    protected array $newGroupOptionIds = [];

    /**
     * @param Helper $helper
     * @param ProductAttributes $productAttributes
     * @param OptionAttributes $optionAttributes
     * @param ResourceConnection $resource
     * @param OptionValueAttributes $optionValueAttributes
     */
    public function __construct(
        ProductAttributes $productAttributes,
        OptionAttributes $optionAttributes,
        OptionValueAttributes $optionValueAttributes,
        ResourceConnection $resource,
        Helper $helper
    ) {
        $this->productAttributes     = $productAttributes;
        $this->optionAttributes      = $optionAttributes;
        $this->optionValueAttributes = $optionValueAttributes;
        $this->resource              = $resource;
        $this->helper                = $helper;
    }

    /**
     * Add attribute data to attribute data array
     *
     * @param string $tableName
     * @param array $data
     */
    public function addAttributeData($tableName, $data)
    {
        if (!empty($data['save'])) {
            foreach ($data['save'] as $dataItem) {
                $this->attributeData[$tableName]['save'][] = $dataItem;
            }
        }
        if (!empty($data['delete'])) {
            foreach ($data['delete'] as $dataItem) {
                $this->attributeData[$tableName]['delete'][] = $dataItem;
            }
        }
    }

    /**
     * Get attribute data array
     *
     * @return array
     */
    public function getAttributeData()
    {
        return $this->attributeData;
    }

    /**
     * Clear attribute data array
     */
    public function clearAttributeData()
    {
        $this->attributeData = [];
    }

    /**
     * Delete old data from attributes
     *
     */
    public function deleteOldAttributeData(array $collectedData, string $entityType): void
    {
        foreach ($collectedData as $tableName => $dataArray) {
            if (empty($dataArray['delete'])) {
                continue;
            }
            $this->deleteProductAttributesProcess($tableName, $entityType, $dataArray);
            $this->deleteOptionAttributesProcess($tableName, $entityType, $dataArray);
            $this->deleteOptionValueAttributesProcess($tableName, $entityType, $dataArray);
        }
    }

    /**
     * Delete product attributes process
     */
    public function deleteProductAttributesProcess(string $tableName, string $entityType, array $dataArray): void
    {
        $this->attributesDeleteProcessHandler($tableName, $entityType, $dataArray, $this->productAttributes);
    }

    /**
     * Delete option attributes process
     */
    public function deleteOptionAttributesProcess(string $tableName, string $entityType, array $dataArray): void
    {
        $this->attributesDeleteProcessHandler($tableName, $entityType, $dataArray, $this->optionAttributes);
    }

    /**
     * Delete option value attributes process
     */
    public function deleteOptionValueAttributesProcess(string $tableName, string $entityType, array $dataArray): void
    {
        $this->attributesDeleteProcessHandler($tableName, $entityType, $dataArray, $this->optionValueAttributes);
    }

    /**
     * Delete attributes handler
     */
    protected function attributesDeleteProcessHandler(
        string $tableName,
        string $entityType,
        array $dataArray,
        AttributeDataInterface $attributes
    ): void {
        $attributeData = $attributes->getData();
        if (!empty($attributeData) && is_array($attributeData)) {
            foreach ($attributeData as $attribute) {
                if ($tableName == $this->resource->getTableName($attribute->getTableName($entityType))) {
                    $attribute->deleteOldData($dataArray['delete']);
                }
            }
        }
    }

    /**
     * Set new options group option IDs
     *
     * @param int $groupOptionId
     * @return void
     */
    public function addNewGroupOptionIds($groupOptionId)
    {
        $this->newGroupOptionIds[] = $groupOptionId;
    }

    /**
     * Get new options group option IDs
     *
     * @return array
     */
    public function getNewGroupOptionIds()
    {
        return $this->newGroupOptionIds;
    }
}
