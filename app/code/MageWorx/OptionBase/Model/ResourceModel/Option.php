<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionBase\Model\ResourceModel;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Model\ResourceModel\Db\Context;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionTemplates\Helper\Data as Helper;

class Option extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    const CATALOG_PRODUCT_OPTION_TABLE_NAME = 'catalog_product_option';
    const CATALOG_PRODUCT_ENTITY_TABLE_NAME = 'catalog_product_entity';

    protected BaseHelper $baseHelper;

    public function __construct(
        Context $context,
        BaseHelper $baseHelper
    ) {
        $this->baseHelper = $baseHelper;
        parent::__construct($context);
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(static::CATALOG_PRODUCT_OPTION_TABLE_NAME, 'option_id');
    }

    /**
     * Check if products exist in magento
     *
     * @param array $skus
     * @return bool
     */
    public function isProductsExist($skus)
    {
        $select = $this->getConnection()
                       ->select()
                       ->from(
                           ['product' => $this->getTable('catalog_product_entity')],
                           'COUNT(*)'
                       )
                       ->where("product.sku IN (?)", $skus);

        return (bool)$this->getConnection()->fetchOne($select);
    }

    /**
     * Check if custom options exist in magento
     *
     * @return bool
     */
    public function isCustomOptionsExist()
    {
        $select = $this->getConnection()
                       ->select()
                       ->from(
                           $this->getTable(static::CATALOG_PRODUCT_OPTION_TABLE_NAME),
                           'COUNT(*)'
                       )
                       ->where(
                           "option_id > 0"
                       );

        return (bool)$this->getConnection()->fetchOne($select);
    }

    /**
     * Check if custom options in magento intersect with SKUs
     *
     * @param array $skus
     * @return bool
     */
    public function hasIntersectingProducts($skus)
    {
        $linkField = $this->baseHelper->getLinkField(ProductInterface::class);
        $select    = $this->getConnection()
                          ->select()
                          ->from(
                              ['option' => $this->getTable(static::CATALOG_PRODUCT_OPTION_TABLE_NAME)],
                              'COUNT(*)'
                          )
                          ->joinLeft(
                              ['product' => $this->getTable('catalog_product_entity')],
                              'product.' . $linkField . ' = option.product_id'
                          )
                          ->where("product.sku IN (?)", $skus);

        return (bool)$this->getConnection()->fetchOne($select);
    }

    /**
     * Remove all customizable option from Magento
     *
     * @used in OptionImportExport full APO Magento1 import
     *
     * @return void
     */
    public function removeAllCustomizableOptionFromMagento()
    {
        $this->removeCustomizableOptions(true);
    }

    /**
     * Remove customizable options from specific products
     *
     * @used in OptionImportExport full APO Magento1 import
     *
     * @param bool $isFullReset
     * @param array $skus
     * @return void
     */
    public function removeCustomizableOptions($isFullReset, $skus = [])
    {
        $linkField = $this->baseHelper->getLinkField();
        if ($isFullReset) {
            $this->getConnection()->delete(
                $this->getTable(static::CATALOG_PRODUCT_OPTION_TABLE_NAME)
            );

            $this->removeHasOptionsFlag();
            $this->removeTemplateRelations(true);

        } elseif ($skus && is_array($skus)) {
            $select = $this->getConnection()
                           ->select()
                           ->from(
                               ['option' => $this->getTable(static::CATALOG_PRODUCT_OPTION_TABLE_NAME)]
                           )
                           ->joinLeft(
                               ['product' => $this->getTable('catalog_product_entity')],
                               'product.' . $linkField . ' = option.product_id'
                           )
                           ->where("product.sku IN (?)", $skus);
            $sql    = $select->deleteFromSelect('option');
            $this->getConnection()->query($sql);

            $this->removeTemplateRelations(false, $skus);
        }
    }

    /** Update has_options, required_options, mageworx_is_require flags for products
     * @param $data
     * @param $productIds
     */
    public function updateProductStatusAttributes($data, $productIds)
    {
        $tableName = $this->getTable(static::CATALOG_PRODUCT_ENTITY_TABLE_NAME);
        $this->getConnection()->update(
            $tableName,
            $data,
            [$this->baseHelper->getLinkField() . ' IN(?)' => $productIds]
        );
    }

    /**
     * Remove has_options and required_options flags from products
     *
     * @used in OptionImportExport full APO Magento1 import
     *
     * @return void
     */
    protected function removeHasOptionsFlag()
    {
        $tableName = $this->getTable(static::CATALOG_PRODUCT_ENTITY_TABLE_NAME);
        $data      = [
            'has_options'      => 0,
            'required_options' => 0,
        ];
        $this->getConnection()->update($tableName, $data);
    }

    /**
     * Remove customizable options from specific products
     *
     * @used in OptionImportExport full APO Magento1 import
     *
     * @param bool $isFullReset
     * @param array $skus
     * @return void
     */
    public function removeTemplateRelations($isFullReset, $skus = [])
    {
        if ($isFullReset) {
            $this->getConnection()->delete(
                $this->getTable(Helper::TABLE_NAME_RELATION)
            );
            return;
        }

        if (!$skus || !is_array($skus)) {
            return;
        }

        $linkField = $this->baseHelper->getLinkField();

        $select = $this->getConnection()
                       ->select()
                       ->from(
                           ['relation' => $this->getTable(Helper::TABLE_NAME_RELATION)]
                       )
                       ->joinLeft(
                           ['product' => $this->getTable('catalog_product_entity')],
                           'product.' . $linkField . ' = relation.product_id'
                       )
                       ->where("product.sku IN (?)", $skus);
        $sql    = $select->deleteFromSelect('relation');

        $this->getConnection()->query($sql);
    }


    /**
     * Get array of option types.
     * ['option_id' => 'type']
     *
     * @param int $productId
     * @return array
     */
    public function getOptionTypes($productId)
    {
        $connection = $this->getConnection();

        $select = $connection->select()->from(
            $this->getTable(static::CATALOG_PRODUCT_OPTION_TABLE_NAME),
            ['option_id', 'type']
        )->where(
            'product_id = ' . $productId
        );

        return $connection->fetchPairs($select);
    }
}
