<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionBase\Model\Product;

use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\ResourceConnection;
use MageWorx\OptionBase\Model\ProductAttributes;
use MageWorx\OptionBase\Api\ProductAttributeInterface;

abstract class AbstractProductAttribute implements ProductAttributeInterface
{
    /**
     * @var \MageWorx\OptionBase\Model\Entity\Product|\MageWorx\OptionBase\Model\Entity\Group
     */
    protected $entity;
    protected ResourceConnection $resource;
    protected DataObjectFactory $dataObjectFactory;

    public function __construct(
        ResourceConnection $resource,
        DataObjectFactory $dataObjectFactory
    ) {
        $this->resource          = $resource;
        $this->dataObjectFactory = $dataObjectFactory;
    }

    /**
     * Get name of attribute
     *
     * @return string
     */
    public function getName()
    {
        return '';
    }

    /**
     * Get table name, used when attribute use individual tables
     *
     * @param string $type
     * @return string
     */
    public function getTableName($type = '')
    {
        $map = [
            'product' => ProductAttributes::TABLE_NAME,
            'group'   => ProductAttributes::OPTIONTEMPLATES_TABLE_NAME
        ];
        return $type ? $map[$type] : $map[$this->entity->getType()];
    }

    /**
     * Collect product attribute data
     *
     * @param \MageWorx\OptionBase\Model\Entity\Group|\MageWorx\OptionBase\Model\Entity\Product $entity
     * @return array
     */
    public function collectData($entity)
    {
        $this->entity = $entity;
        $data         = [];

        if ($entity->getType() === 'product') {
            $linkField   = $entity->getDataObject()->getResource()->getLinkField();
            $linkFieldId = $entity->getDataObject()->getData($linkField);

            $attributeValue = $entity->getDataObject()->getData($this->getName());
            if (isset($attributeValue)) {
                $data['save'][$linkFieldId][$this->getName()] = $attributeValue;
            };

            $data['delete'][$linkFieldId] = [
                'product_id' => $linkFieldId
            ];
        } elseif ($entity->getType() === 'group') {
            $linkField = $entity->getDataObjectIdName();

            $attributeValue = $entity->getDataObject()->getData($this->getName());
            if (!isset($attributeValue)) {
                return $data;
            };

            $connection = $this->resource->getConnection();
            $tableName  = $this->resource->getTableName($this->getTableName());

            $connection->update(
                $tableName,
                [$this->getName() => $attributeValue],
                $linkField . " = '" . $entity->getDataObject()->getData($linkField) . "'"
            );
        }

        return $data;
    }

    /**
     * Delete old product attribute data
     *
     * @array $data
     * @return void
     */
    public function deleteOldData(array $data)
    {
        $productIds = [];
        foreach ($data as $dataItem) {
            $productIds[$dataItem['product_id']] = $dataItem['product_id'];
        }
        if (!$productIds) {
            return;
        }
        $tableName = $this->resource->getTableName($this->getTableName());
        $this->resource->getConnection()->delete($tableName, ['product_id IN (?)' => $productIds]);
    }

    /**
     * Get default value of attribute
     *
     * @return int|string
     */
    public function getDefaultValue()
    {
        return 0;
    }

    /**
     * Get priority value of attribute key
     *
     * @return null|int|string
     */
    public function getPriorityValue()
    {
        return null;
    }

    /**
     * Validate Magento 1 template import
     *
     * @param array $groupData
     * @throws \Exception
     * @throws LocalizedException
     */
    public function validateTemplateImportMageOne($groupData)
    {
        // TODO...
        return;
    }

    /**
     * Import Magento 1 template data
     *
     * @param array $groupData
     * @return array
     */
    public function importTemplateMageOne($groupData)
    {
        return [];
    }

    /**
     * Validate Magento 2 template import
     *
     * @param array $groupData
     * @throws \Exception
     * @throws LocalizedException
     */
    public function validateTemplateImportMageTwo($groupData)
    {
        //TODO...
        return;
    }

    /**
     * Import Magento 2 template data
     *
     * @param array $groupData
     * @return array
     */
    public function importTemplateMageTwo($groupData)
    {
        return [];
    }

    /**
     * Prepare Magento 1 product attribute for import
     *
     * @param array $productAttributesData
     * @param array $data
     * @return void
     */
    public function prepareOptionsMageOne(&$productAttributesData, $data)
    {
        if (!is_array($data)) {
            return;
        }

        foreach ($data as $sku => $product) {
            $productData = [];

            $attributeKey = $this->getName();
            if (!isset($product['_' . $attributeKey])) {
                continue;
            }
            $productData[$attributeKey] = $product['_' . $attributeKey];

            if (isset($productAttributesData[$sku])) {
                $productAttributesData[$sku] = array_merge($productAttributesData[$sku], $productData);
            } else {
                $productAttributesData[$sku] = $productData;
            }
        }
    }

    /**
     * Flag to check if attribute should be skipped during Magento 2 export
     *
     * @return bool
     */
    public function shouldSkipExportMageTwo()
    {
        return false;
    }

    /**
     * Collect data for magento2 product import
     *
     * @param array $row
     * @return array|null
     */
    public function collectImportDataMageTwo($row)
    {
        $this->entity = $this->dataObjectFactory->create();
        $this->entity->setData('type', 'product');

        $data      = [];
        $productId = $row['product_id'];

        $data['save'][$productId][$this->getName()] = $row[$this->getName()] ?? $this->getDefaultValue();
        $data['delete'][$productId]                 = [
            'product_id' => $productId
        ];
        return $data;
    }
}
