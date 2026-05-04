<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionTemplates\Model\ResourceModel;

use Magento\Catalog\Model\ProductFactory as ProductFactory;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Stdlib\DateTime as LibDateTime;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\StoreManagerInterface;
use MageWorx\OptionTemplates\Model\Group as GroupModel;
use MageWorx\OptionBase\Helper\Data as BaseHelper;

class Group extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    const OPTION_TEMPLATES_GROUP_TABLE_NAME = 'mageworx_optiontemplates_group';

    /**
     *
     * @var string
     */
    protected $productRelationTable = 'mageworx_optiontemplates_relation';

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $date;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    protected $dateTime;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var BaseHelper
     */
    protected $baseHelper;

    /**
     * @param Context $context
     * @param DateTime $date
     * @param StoreManagerInterface $storeManager
     * @param ProductFactory $productFactory
     * @param LibDateTime $dateTime
     * @param ManagerInterface $eventManager
     * @param BaseHelper $baseHelper
     */
    public function __construct(
        Context $context,
        DateTime $date,
        StoreManagerInterface $storeManager,
        ProductFactory $productFactory,
        LibDateTime $dateTime,
        ManagerInterface $eventManager,
        BaseHelper $baseHelper
    ) {
        $this->date           = $date;
        $this->storeManager   = $storeManager;
        $this->dateTime       = $dateTime;
        $this->eventManager   = $eventManager;
        $this->productFactory = $productFactory;
        $this->baseHelper     = $baseHelper;

        parent::__construct($context);
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(static::OPTION_TEMPLATES_GROUP_TABLE_NAME, 'group_id');
    }

    /**
     * Retrieve default values for create
     *
     * @return array
     */
    public function getDefaultValues()
    {
        return [
            'assign_type' => \MageWorx\OptionTemplates\Model\Group\Source\AssignType::ASSIGN_BY_GRID,
        ];
    }

    /**
     * Before save callback
     *
     * @param AbstractModel|\MageWorx\OptionTemplates\Model\Group $object
     * @return $this
     */
    protected function _beforeSave(AbstractModel $object)
    {
        $object->setUpdatedAt($this->date->gmtDate());

        return parent::_beforeSave($object);
    }

    /**
     *
     * @param int $groupId
     * @return array
     */
    public function getGroupOptionIdsByGroupId($groupId)
    {
        $select = $this->getConnection()
                       ->select()
                       ->from(
                           ['main_table' => $this->getMainTable()],
                           []
                       )
                       ->join(
                           ['group_option_table' => $this->getTable('mageworx_optiontemplates_group_option')],
                           'main_table.group_id = group_option_table.group_id',
                           ['option_id']
                       )
                       ->where('main_table.group_id = ?', $groupId);

        return $this->getConnection()->fetchCol($select);
    }

    /**
     * Get group option IDs by product ID
     *
     * @param int $productId
     * @return array
     */
    public function getGroupOptionIdsByProductId($productId)
    {
        $select = $this->getConnection()
                       ->select()
                       ->from(
                           ['group_option_table' => $this->getTable('mageworx_optiontemplates_group_option')],
                           ['option_id']
                       )
                       ->joinLeft(
                           ['group_table' => $this->getTable('mageworx_optiontemplates_group')],
                           'group_table.group_id = group_option_table.group_id',
                           []
                       )
                       ->joinLeft(
                           ['relation_table' => $this->getTable('mageworx_optiontemplates_relation')],
                           'group_table.group_id = relation_table.group_id',
                           []
                       )
                       ->where('relation_table.product_id = ?', $productId);

        return $this->getConnection()->fetchCol($select);
    }

    /**
     * Remove incorrectly linked options by product ID
     *
     * @param int $productId
     * @return void
     */
    public function removeIncorrectlyLinkedOptions($productId)
    {
        $linkField      = $this->baseHelper->getLinkField();
        $groupOptionIds = $this->getGroupOptionIdsByProductId($productId);

        $select = $this->getConnection()
                       ->select()
                       ->from(
                           ['option' => $this->getTable('catalog_product_option')]
                       )
                       ->joinLeft(
                           ['product' => $this->getTable('catalog_product_entity')],
                           'product.' . $linkField . ' = option.product_id'
                       )
                       ->where('product.' . $linkField . ' = ' . (int)$productId);

        if (!is_array($groupOptionIds) || empty($groupOptionIds)) {
            $select->where('group_option_id IS NOT NULL');
        } else {
            $select->where('group_option_id IS NOT NULL AND group_option_id NOT IN (?)', $groupOptionIds);
        }

        $sql    = $select->deleteFromSelect('option');
        $this->getConnection()->query($sql);
    }

    /**
     *
     * @param int $productId
     * @return array
     */
    public function getProductOptionToGroupRelations($productId)
    {
        $select = $this->getConnection()
                       ->select()
                       ->from(
                           ['product_option_table' => $this->getTable('catalog_product_option')],
                           ['product_option_table.option_id', 'group_option_table.group_id']
                       )
                       ->join(
                           ['group_option_table' => $this->getTable('mageworx_optiontemplates_group_option')],
                           'product_option_table.group_option_id = group_option_table.option_id'
                       )
                       ->where('product_option_table.product_id = ?', (int)$productId)
                       ->where('product_option_table.group_option_id IS NOT NULL');
        return $this->getConnection()->fetchPairs($select);
    }

    /**
     * After save callback
     *
     * @param AbstractModel $object
     * @return $this
     */
    protected function _afterSave(\Magento\Framework\Model\AbstractModel $object)
    {
        $object->setProductRelation();

        return parent::_afterSave($object);
    }

    /**
     * Clear all template relations
     *
     * @param \MageWorx\OptionTemplates\Model\Group $object
     * @return $this
     */
    public function clearProductRelation(\MageWorx\OptionTemplates\Model\Group $object)
    {
        $id        = $object->getId();
        $condition = ['group_id=?' => $id];
        $this->getConnection()->delete($this->getTable($this->productRelationTable), $condition);
        $object->setIsChangedProductList(true);

        return $this;
    }

    /**
     * @param GroupModel|int $group
     * @return array
     */
    public function getProducts($group)
    {
        $groupId = is_object($group) ? $groupId = $group->getId() : (int)$group;

        $select = $this->getConnection()
                       ->select()
                       ->from(
                           $this->getTable($this->productRelationTable),
                           ['product_id']
                       )
                       ->where(
                           'group_id = :group_id'
                       );
        $bind   = ['group_id' => $groupId];

        return $this->getConnection()->fetchCol($select, $bind);
    }

    /**
     * @param GroupModel|int $group
     * @return array
     */
    public function getProductSku($group)
    {
        $groupId = is_object($group) ? $groupId = $group->getId() : (int)$group;

        $linkField = $this->baseHelper->getLinkField();
        $select    = $this->getConnection()
                          ->select()
                          ->from(
                              $this->getTable($this->productRelationTable),
                              ['product_id']
                          )
                          ->joinLeft(
                              ['cpe' => $this->getTable('catalog_product_entity')],
                              'cpe.' . $linkField . ' = ' . $this->getTable(
                                  $this->productRelationTable
                              ) . '.product_id',
                              'cpe.sku'
                          )
                          ->where(
                              'group_id = :group_id'
                          );
        $bind      = ['group_id' => $groupId];

        return $this->getConnection()->fetchPairs($select, $bind);
    }

    /**
     * Add group product relation by group Id
     *
     * @param int $groupId
     * @param int $productId
     * @return int|null
     */
    public function addProductRelation($groupId, $productId)
    {
        if ($productId && $groupId) {
            $adapter = $this->getConnection();

            $data = [
                'group_id'   => (int)$groupId,
                'product_id' => (int)$productId,
                'is_changed' => 0
            ];

            return $adapter->insert($this->getTable($this->productRelationTable), $data);
        }

        return null;
    }

    /**
     * Delete group product relation by group ID
     *
     * @param int $groupId
     * @param int $productId
     * @return int|null
     */
    public function deleteProductRelation($groupId, $productId)
    {
        if (!empty($productId) && $groupId) {
            $adapter   = $this->getConnection();
            $condition = ['product_id IN(?)' => (int)$productId, 'group_id=?' => (int)$groupId];

            return $adapter->delete($this->getTable($this->productRelationTable), $condition);
        }

        return null;
    }

    /**
     * Delete all group-product relations by product ID
     *
     * @param int $productId
     * @return int|null
     */
    public function removeProductRelations($productId)
    {
        if (!empty($productId)) {
            $adapter   = $this->getConnection();
            $condition = ['product_id = (?)' => (int)$productId];

            return $adapter->delete($this->getTable($this->productRelationTable), $condition);
        }

        return null;
    }

    /**
     * Save group title
     *
     * @param int $groupId
     * @param string $title
     * @return void
     */
    public function saveTitle($groupId, $title)
    {
        $this->getConnection()->update(
            $this->_resources->getTableName(static::OPTION_TEMPLATES_GROUP_TABLE_NAME),
            ['title' => $title],
            "group_id = '" . $groupId . "'"
        );
    }

    /**
     * Find and set unique title for group
     *
     * @param GroupModel $group
     * @return void
     */
    public function findUniqueGroupTitle($group)
    {
        $isGroupSaved = false;
        do {
            if (!$this->isGroupTitleExist($group)) {
                return;
            }
            $groupTitle = $group->getTitle();
            $groupTitle = preg_match('/(.*)-(\d+)$/', $groupTitle, $matches)
                ? $matches[1] . '-' . ($matches[2] + 1)
                : $groupTitle . '-1';
            $group->setTitle($groupTitle);
        } while (!$isGroupSaved);
    }

    /**
     * Check if group title already exist
     *
     * @param GroupModel $group
     * @return bool
     */
    public function isGroupTitleExist($group)
    {
        $title        = $group->getTitle();
        $query        = $this->getConnection()
                             ->select()
                             ->from(['main_table' => $this->getMainTable()])
                             ->reset(\Magento\Framework\DB\Select::COLUMNS)
                             ->columns(['title'])
                             ->where('title = ?', $title);
        $isTitleExist = $this->getConnection()->fetchOne($query);
        return (bool)$isTitleExist;
    }

    /**
     * Get all existing pairs of group ID-title
     *
     * @return array
     */
    public function getAllGroupsData()
    {
        $query     = $this->getConnection()
                          ->select()
                          ->from(['main_table' => $this->getMainTable()], ['group_id', 'title'])
                          ->where('1');
        $groupData = $this->getConnection()->fetchPairs($query);
        return $groupData ?? [];
    }

    /**
     * Get option/option value ids and sort_orders to create template relations with imported product options
     *
     * @param array $groupIds
     * @return array
     */
    public function getTemplateMapForImport($groupIds)
    {
        $select = $this->getConnection()
                       ->select()
                       ->from(
                           [
                               'group_option_value' => $this->getTable(
                                   'mageworx_optiontemplates_group_option_type_value'
                               )
                           ],
                           [
                               'value_sort_order' => 'group_option_value.sort_order',
                               'value_id'         => 'group_option_value.option_type_id'
                           ]
                       )
                       ->joinLeft(
                           ['group_option' => $this->getTable('mageworx_optiontemplates_group_option')],
                           'group_option.option_id = group_option_value.option_id',
                           [
                               'option_sort_order' => 'group_option.sort_order',
                               'option_id'         => 'group_option.option_id'
                           ]
                       )
                       ->joinLeft(
                           ['gr' => $this->getTable('mageworx_optiontemplates_group')],
                           'group_option.group_id = gr.group_id',
                           ['group_id' => 'gr.group_id']
                       )
                       ->where('gr.group_id IN (' . implode(',', array_keys($groupIds)) . ')');

        $selectableOptions = $this->getConnection()->fetchAll($select);

        $select = $this->getConnection()
                       ->select()
                       ->from(
                           ['group_option' => $this->getTable('mageworx_optiontemplates_group_option')],
                           [
                               'option_sort_order' => 'group_option.sort_order',
                               'option_id'         => 'group_option.option_id'
                           ]
                       )
                       ->joinLeft(
                           ['gr' => $this->getTable('mageworx_optiontemplates_group')],
                           'group_option.group_id = gr.group_id',
                           ['group_id' => 'gr.group_id']
                       )
                       ->where(
                           "gr.group_id IN (" . implode(",", array_keys($groupIds)) . ") " .
                           "AND group_option.type IN ('field','area','date','date_time','time','file')"
                       );

        $nonSelectableOptions = $this->getConnection()->fetchAll($select);

        return array_merge($selectableOptions, $nonSelectableOptions);
    }

    /**
     * Check if groups contain priority value of one of the attributes
     *
     * @param string $fieldName
     * @param string $priorityValue
     * @param array $groupIds
     * @return bool
     */
    public function hasPriorityValue($fieldName, $priorityValue, array $groupIds)
    {
        $select = $this->getConnection()
                       ->select()
                       ->from(
                           $this->getMainTable(),
                           'COUNT(*)'
                       )
                       ->where($fieldName . " = ?", $priorityValue)
                       ->where("group_id IN (?)", $groupIds);

        return (bool)$this->getConnection()->fetchOne($select);
    }

    /**
     * Get related groupIds by productId
     *
     * @param int $productId
     * @return array
     */
    public function getGroupIds($productId)
    {
        $linkField = $this->baseHelper->getLinkField();
        $select    = $this->getConnection()
                          ->select()
                          ->from(['cpe' => $this->getTable('catalog_product_entity')], [])
                          ->joinLeft(
                              ['relation_table' => $this->getTable('mageworx_optiontemplates_relation')],
                              'cpe.' . $linkField . ' = relation_table.product_id',
                              ['group_id']
                          )
                          ->where('relation_table.product_id = ?', $productId);

        return $this->getConnection()->fetchCol($select);
    }

    /**
     * Get related group IDs and titles by productId
     *
     * @param int $productId
     * @return array
     */
    public function getGroupData($productId)
    {
        $linkField = $this->baseHelper->getLinkField();
        $select    = $this->getConnection()
                          ->select()
                          ->from(
                              ['group_table' => $this->getTable('mageworx_optiontemplates_group')],
                              ['group_id', 'title']
                          )
                          ->joinLeft(
                              ['relation_table' => $this->getTable('mageworx_optiontemplates_relation')],
                              'group_table.group_id = relation_table.group_id',
                              []
                          )
                          ->joinLeft(
                              ['cpe' => $this->getTable('catalog_product_entity')],
                              'cpe.' . $linkField . ' = relation_table.product_id',
                              []
                          )
                          ->where('relation_table.product_id = ?', $productId);

        return $this->getConnection()->fetchPairs($select);
    }

    /**
     * Copy template relation for duplicated product
     *
     * @param $newProductId
     * @param $oldProductId
     */
    public function duplicateTemplateRelations($newProductId, $oldProductId)
    {
        $table  = $this->getTable('mageworx_optiontemplates_relation');
        $select = $this->getConnection()->select()->from(
            $table,
            ['group_id', new \Zend_Db_Expr($newProductId), 'option_id', 'is_changed']
        )->where(
            'product_id = ?',
            $oldProductId
        );
        $insertSelect = $this->getConnection()->insertFromSelect(
            $select,
            $table,
            ['group_id', 'product_id', 'option_id', 'is_changed'],
            \Magento\Framework\DB\Adapter\AdapterInterface::INSERT_ON_DUPLICATE
        );
        $this->getConnection()->query($insertSelect);
    }
}
