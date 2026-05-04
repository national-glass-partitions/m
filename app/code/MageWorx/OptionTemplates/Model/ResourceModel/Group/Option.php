<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionTemplates\Model\ResourceModel\Group;

use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use MageWorx\OptionTemplates\Model\ResourceModel\Group\Option\Value as ValueResourceModel;
use MageWorx\OptionBase\Model\Product\Option\Attributes as OptionAttributes;
use Magento\Framework\Registry;

class Option extends \Magento\Catalog\Model\ResourceModel\Product\Option
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
     * @var ValueResourceModel
     */
    protected $valueResourceModel;

    /**
     * @var OptionAttributes
     */
    protected $optionAttributes;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var int
     */
    protected $oldGroupId;

    /**
     * @var int
     */
    protected $newGroupId;

    /**
     * @var int
     */
    protected $oldOptionId;

    /**
     * @var int
     */
    protected $newOptionId;

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
     * @param ValueResourceModel $valueResourceModel
     * @param OptionAttributes $optionAttributes
     * @param Registry $registry
     * @param string $connectionName
     */
    public function __construct(
        Context $context,
        CurrencyFactory $currencyFactory,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $config,
        ValueResourceModel $valueResourceModel,
        OptionAttributes $optionAttributes,
        Registry $registry,
        $connectionName = null
    ) {
        $this->registry = $registry;
        $this->valueResourceModel = $valueResourceModel;
        $this->optionAttributes = $optionAttributes;
        parent::__construct($context, $currencyFactory, $storeManager, $config, $connectionName);
    }

    /**
     * Define main table and initialize connection
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('mageworx_optiontemplates_group_option', 'option_id');
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
     * Delete option
     *
     * @param int $optionId
     * @return void
     */
    public function deleteOldOptions($groupId)
    {
        $condition = ['group_id = ?' => $groupId];

        $this->getConnection()->delete($this->getTable('catalog_product_option'), $condition);
    }

    /**
     * Duplicate custom options for group
     *
     * @param int $oldGroupId
     * @param int $newGroupId
     * @return void
     */
    public function duplicateOptions($oldGroupId, $newGroupId)
    {
        $connection = $this->getConnection();

        $optionsCond = [];
        $oldIds = [];
        $mapId = [];

        $this->oldGroupId = $oldGroupId;
        $this->newGroupId = $newGroupId;

        $optionsData = $this->processIds($oldIds);

        foreach ($optionsData as $oId => $data) {
            $connection->insert($this->getMainTable(), $data);
            $optionsCond[$oId] = $connection->lastInsertId($this->getMainTable());
        }

        foreach ($optionsCond as $oldOptionId => $newOptionId) {
            $this->oldOptionId = $oldOptionId;
            $this->newOptionId = $newOptionId;

            $this->copyTitle();
            $this->copyPrice();
            $this->processMageWorxAttributes($oldIds);

            $this->valueResourceModel->duplicateValue($oldOptionId, $newOptionId);

            // used in the DuplicateDependency plugin
            $mapId[$this->oldId] = $this->newId;
        }

        $mapOptionId = $this->registry->registry('mapOptionId');
        if (!isset($mapOptionId)) {
            $this->registry->register('mapOptionId', $mapId);
        }
    }

    /**
     * Collect option's data, remove old mageworx IDs, change old group ID to the new one
     *
     * @param array $oldIds
     * @return array
     */
    protected function processIds(&$oldIds)
    {
        $optionsData = [];
        $connection = $this->getConnection();

        $select = $connection->select()->from(
            $this->getTable('catalog_product_option')
        )->where(
            'group_id = ?',
            $this->oldGroupId
        );

        $query = $connection->query($select);

        while ($row = $query->fetch()) {
            $oldIds[$row['option_id']] = $row['option_id'];

            $optionsData[$row['option_id']] = $row;
            $optionsData[$row['option_id']]['group_id'] = $this->newGroupId;
            $optionsData[$row['option_id']]['option_id'] = null;
            unset($optionsData[$row['option_id']]['option_id']);
        }

        return $optionsData;
    }

    /**
     * Copy title info
     *
     * @return void
     */
    protected function copyTitle()
    {
        $connection = $this->getConnection();
        $table = $this->getTable('catalog_product_option_title');

        $select = $this->getConnection()->select()->from(
            $table,
            [new \Zend_Db_Expr($this->newOptionId), 'store_id', 'title']
        )->where(
            'option_id = ?',
            $this->oldOptionId
        );

        $insertSelect = $connection->insertFromSelect(
            $select,
            $table,
            ['option_id', 'store_id', 'title'],
            \Magento\Framework\DB\Adapter\AdapterInterface::INSERT_ON_DUPLICATE
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
        $table = $this->getTable('catalog_product_option_price');

        $select = $connection->select()->from(
            $table,
            [new \Zend_Db_Expr($this->newOptionId), 'store_id', 'price', 'price_type']
        )->where(
            'option_id = ?',
            $this->oldOptionId
        );

        $insertSelect = $connection->insertFromSelect(
            $select,
            $table,
            ['option_id', 'store_id', 'price', 'price_type'],
            \Magento\Framework\DB\Adapter\AdapterInterface::INSERT_ON_DUPLICATE
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
        $table = $this->getTable('catalog_product_option');
        $select = $connection->select()->from(
            $table,
            ['option_id']
        )->where(
            'option_id = ?',
            $this->newOptionId
        );

        $this->oldId = $oldIds[$this->oldOptionId];
        $this->newId = $connection->fetchOne($select);

        foreach ($this->optionAttributes->getData() as $attribute) {
            /** @var \MageWorx\OptionBase\Model\AttributeInterface $attribute */
            $attribute->processDuplicate($this->newId, $this->oldId, 'group');
        }
    }
}
