<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionTemplates\Model\ResourceModel\Group\Option;

use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use MageWorx\OptionBase\Model\Product\Option\Value\Attributes as OptionValueAttributes;
use Magento\Framework\Registry;

class Value extends \Magento\Catalog\Model\ResourceModel\Product\Option\Value
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var CurrencyFactory
     */
    protected $currencyFactory;

    /**
     * @var ScopeConfigInterface
     */
    protected $config;

    /**
     * @var OptionValueAttributes
     */
    protected $optionValueAttributes;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var int
     */
    protected $oldTypeId;

    /**
     * @var int
     */
    protected $newTypeId;

    /**
     * @var string
     */
    protected $oldId;

    /**
     * @var string
     */
    protected $newId;

    /**
     * @param Context $context
     * @param CurrencyFactory $currencyFactory
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $config
     * @param OptionValueAttributes $optionValueAttributes
     * @param Registry $registry
     * @param string $connectionName
     */
    public function __construct(
        Context $context,
        CurrencyFactory $currencyFactory,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $config,
        OptionValueAttributes $optionValueAttributes,
        Registry $registry,
        $connectionName = null
    ) {
        $this->registry = $registry;
        $this->optionValueAttributes = $optionValueAttributes;
        parent::__construct($context, $currencyFactory, $storeManager, $config, $connectionName);
    }

    /**
     * Define main table and initialize connection
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('mageworx_optiontemplates_group_option_type_value', 'option_type_id');
    }

    /**
     * Get real table name for db table, validated by db adapter
     * Replace product option tables to mageworx group option tables
     *
     * @param string $origTableName
     * @return string
     *
     */
    public function getTable($origTableName)
    {
        $origTableName = parent::getTable($origTableName);

        switch ($origTableName) {
            case parent::getTable('catalog_product_option'):
                $tableName = 'mageworx_optiontemplates_group_option';
                break;
            case parent::getTable('catalog_product_option_title'):
                $tableName = 'mageworx_optiontemplates_group_option_title';
                break;
            case parent::getTable('catalog_product_option_price'):
                $tableName = 'mageworx_optiontemplates_group_option_price';
                break;
            case parent::getTable('catalog_product_option_type_value'):
                $tableName = 'mageworx_optiontemplates_group_option_type_value';
                break;
            case parent::getTable('catalog_product_option_type_title'):
                $tableName = 'mageworx_optiontemplates_group_option_type_title';
                break;
            case parent::getTable('catalog_product_option_type_price'):
                $tableName = 'mageworx_optiontemplates_group_option_type_price';
                break;
            default:
                $tableName = $origTableName;
        }
        return parent::getTable($tableName);
    }

    /**
     * Delete values by option type
     *
     * @param int $optionTypeId
     * @return void
     */
    public function deleteValues($optionTypeId)
    {
        $condition = ['option_type_id = ?' => $optionTypeId];

        $this->getConnection()->delete($this->getTable('catalog_product_option_type_value'), $condition);
    }

    /**
     * Duplicate group option value
     *
     * @param int $oldOptionId
     * @param int $newOptionId
     * @return void
     */
    public function duplicateValue($oldOptionId, $newOptionId)
    {
        $connection = $this->getConnection();
        $select = $connection->select()->from($this->getMainTable())->where('option_id = ?', $oldOptionId);
        $valueData = $connection->fetchAll($select);

        $valueCond = [];
        $oldIds = [];
        $mapId = [];

        foreach ($valueData as $data) {
            $oldIds[$data['option_type_id']] = $data['option_type_id'];
            $optionTypeId = $data[$this->getIdFieldName()];
            unset($data[$this->getIdFieldName()]);
            $data['option_id'] = $newOptionId;
            $data['option_type_id'] = null;

            $connection->insert($this->getMainTable(), $data);
            $valueCond[$optionTypeId] = $connection->lastInsertId($this->getMainTable());
        }

        unset($valueData);

        foreach ($valueCond as $oldTypeId => $newTypeId) {
            $this->oldTypeId = $oldTypeId;
            $this->newTypeId = $newTypeId;

            $this->copyPrice();
            $this->copyTitle();
            $this->processMageWorxAttributes($oldIds);

            // used in the DuplicateDependency plugin
            $mapId[$this->oldId] = $this->newId;
        }

        $mapOptionTypeId = $this->registry->registry('mapOptionTypeId');
        if (isset($mapOptionTypeId)) {
            $this->registry->unregister('mapOptionTypeId');
            $mapId += $mapOptionTypeId;
        }
        $this->registry->register('mapOptionTypeId', $mapId);
    }

    /**
     * Copy title info
     *
     * @return void
     */
    protected function copyTitle()
    {
        $connection = $this->getConnection();
        $titleTable = $this->getTable('catalog_product_option_type_title');
        $columns = [new \Zend_Db_Expr($this->newTypeId), 'store_id', 'title'];

        $select = $this->getConnection()->select()->from(
            $titleTable,
            []
        )->where(
            'option_type_id = ?',
            $this->oldTypeId
        )->columns(
            $columns
        );
        $insertSelect = $connection->insertFromSelect(
            $select,
            $titleTable,
            ['option_type_id', 'store_id', 'title']
        );
        $connection->query($insertSelect);
    }

    /**
     * Copy price info
     *
     * @return void
     */
    protected function copyPrice()
    {
        $connection = $this->getConnection();
        $priceTable = $this->getTable('catalog_product_option_type_price');
        $columns = [new \Zend_Db_Expr($this->newTypeId), 'store_id', 'price', 'price_type'];

        $select = $connection->select()->from(
            $priceTable,
            []
        )->where(
            'option_type_id = ?',
            $this->oldTypeId
        )->columns(
            $columns
        );
        $insertSelect = $connection->insertFromSelect(
            $select,
            $priceTable,
            ['option_type_id', 'store_id', 'price', 'price_type']
        );
        $connection->query($insertSelect);
    }

    /**
     * Process MageWorx attributes
     *
     * @param array $oldMageworxIds
     * @return void
     */
    protected function processMageWorxAttributes($oldIds)
    {
        $connection = $this->getConnection();
        $table = $this->getTable('catalog_product_option_type_value');
        $select = $connection->select()->from(
            $table,
            ['option_type_id']
        )->where(
            'option_type_id = ?',
            $this->newTypeId
        );

        $this->oldId = $oldIds[$this->oldTypeId];
        $this->newId = $connection->fetchOne($select);

        foreach ($this->optionValueAttributes->getData() as $attribute) {
            /** @var \MageWorx\OptionBase\Model\AttributeInterface $attribute */
            $attribute->processDuplicate($this->newId, $this->oldId, 'group');
        }
    }
}
