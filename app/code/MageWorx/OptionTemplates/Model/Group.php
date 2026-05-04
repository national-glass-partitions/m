<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionTemplates\Model;

use Magento\Catalog\Model\Product\OptionFactory as ProductOptionFactory;
use MageWorx\OptionTemplates\Model\Group\OptionFactory as GroupOptionFactory;
use MageWorx\OptionBase\Model\Entity\Group as GroupEntity;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionBase\Model\ResourceModel\CollectionUpdaterRegistry;

/**
 * Option group model
 *
 * @method \MageWorx\OptionTemplates\Model\Group setProductsIds(array $productIds)
 * @method array getProductsIds()
 * @method $this setNewProductIds(array $insert)
 * @method $this setUpdProductIds(array $update)
 * @method $this setDelProductIds(array $delete)
 * @method $this setAffectedProductIds(array $productIds)
 * @method array getNewProductIds()
 * @method array getUpdProductIds()
 * @method array getDelProductIds()
 * @method array getAffectedProductIds()
 * @method ResourceModel\Group getResource()
 * @method int|string getGroupId()
 */
class Group extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'mageworx_optiontemplates_group';

    /**
     * Product object customization (not stored in DB)
     *
     * @var array
     */
    protected $_customOptions = [];

    /**
     * Group option factory
     *
     * @var Group\OptionFactory
     */
    protected $groupOptionFactory;

    /**
     * Product option collection factory
     *
     * @var \Magento\Catalog\Model\ResourceModel\Product\Option\CollectionFactory
     */
    protected $productOptionCollectionFactory;

    /**
     * Product option
     *
     * @var Group\Option
     */
    protected $groupOptionInstance;

    /**
     * Product option
     *
     * @var \Magento\Catalog\Model\Product\Option
     */
    protected $productOptionInstance;

    /**
     * Product factory
     *
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * Product option factory
     *
     * @var ProductOptionFactory
     */
    protected $productOptionFactory;

    /**
     * @var \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface
     */
    protected $joinProcessor;

    /**
     * @var \Magento\Catalog\Model\ProductOptions\ConfigInterface
     */
    protected $productOptionConfig;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var bool
     */
    protected $optionsInitialized = false;

    /**
     * @var bool
     */
    protected $canAffectOptions = true;

    /**
     * Item option factory
     *
     * @var \Magento\Catalog\Model\Product\Configuration\Item\OptionFactory
     */
    protected $itemOptionFactory;

    /**
     * @var GroupEntity
     */
    protected $groupEntity;

    /**
     * @var BaseHelper
     */
    protected $baseHelper;

    /**
     * @var CollectionUpdaterRegistry
     */
    protected $collectionUpdaterRegistry;

    /**
     *
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Catalog\Model\ProductOptions\ConfigInterface $productOptionConfig
     * @param GroupOptionFactory $groupOptionFactory
     * @param ProductOptionFactory $productOptionFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product\Option\CollectionFactory $productOptionCollectionFactory
     * @param \Magento\Catalog\Model\Product\Configuration\Item\OptionFactory $itemOptionFactory
     * @param \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface $joinProcessor
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param GroupEntity $groupEntity
     * @param BaseHelper $baseHelper
     * @param CollectionUpdaterRegistry $collectionUpdaterRegistry
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Model\ProductOptions\ConfigInterface $productOptionConfig,
        GroupOptionFactory $groupOptionFactory,
        ProductOptionFactory $productOptionFactory,
        \Magento\Catalog\Model\ResourceModel\Product\Option\CollectionFactory $productOptionCollectionFactory,
        \Magento\Catalog\Model\Product\Configuration\Item\OptionFactory $itemOptionFactory,
        \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface $joinProcessor,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        GroupEntity $groupEntity,
        BaseHelper $baseHelper,
        CollectionUpdaterRegistry $collectionUpdaterRegistry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->baseHelper = $baseHelper;
        $this->joinProcessor = $joinProcessor;
        $this->productFactory = $productFactory;
        $this->productOptionConfig = $productOptionConfig;
        $this->groupOptionFactory = $groupOptionFactory;
        $this->productOptionFactory = $productOptionFactory;
        $this->productOptionCollectionFactory = $productOptionCollectionFactory;
        $this->itemOptionFactory = $itemOptionFactory;
        $this->groupEntity = $groupEntity;
        $this->collectionUpdaterRegistry = $collectionUpdaterRegistry;

        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('MageWorx\OptionTemplates\Model\ResourceModel\Group');
    }

    /**
     * Retrieve default values
     *
     * @return array
     */
    public function getDefaultValues()
    {
        return [
            'assign_type' => \MageWorx\OptionTemplates\Model\Group\Source\AssignType::ASSIGN_BY_GRID,
            'is_active' => 1,
            'store_id' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
        ];
    }

    /**
     * Set related product IDs to model and retrieve it as array
     *
     * @return array
     */
    public function getProducts()
    {
        if (!$this->getId()) {
            return [];
        }

        /** @var \MageWorx\OptionTemplates\Model\ResourceModel\Group * */
        $productIds = $this->getResource()->getProducts($this);
        $this->setData('products', $productIds);

        return $productIds;
    }

    /**
     * Retrieve option instance
     *
     * @return Group\Option
     */
    public function getGroupOptionInstance()
    {
        if (!isset($this->groupOptionInstance)) {
            $this->groupOptionInstance = $this->groupOptionFactory->create();
            $this->groupOptionInstance->setProduct($this->convertGroupToProduct());
        }

        return $this->groupOptionInstance;
    }

    /**
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return \Magento\Catalog\Model\Product\Option
     */
    public function getProductOptionInstance($product)
    {
        if (!isset($this->productOptionInstance)) {
            $this->productOptionInstance = $this->productOptionFactory->create();
            $this->productOptionInstance->setProduct($product);
        }

        return $this->productOptionInstance;
    }

    /**
     * Retrieve options collection of product
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Option\Collection
     */
    public function getGroupOptionsCollection()
    {
        $collection = $this->getGroupOptionInstance()->getProductOptionCollection($this->getGroupOptionInstance()->getProduct());

        return $collection;
    }

    /**
     * Add option to array of product options
     *
     * @param Group\Option $option
     * @return \Magento\Catalog\Model\Product
     */
    public function addOption(Group\Option $option)
    {
        $this->options[$option->getId()] = $option;

        return $this;
    }

    /**
     * Get option from options array of product by given option id
     *
     * @param int $optionId
     * @return \Magento\Catalog\Model\Product\Option|null
     */
    public function getOptionById($optionId)
    {
        if (isset($this->options[$optionId])) {
            return $this->options[$optionId];
        }

        return null;
    }

    /**
     * Get all options of group
     *
     * @return array
     */
    public function getOptions()
    {
        $this->collectionUpdaterRegistry->setCurrentEntityType('group');
        $this->collectionUpdaterRegistry->setCurrentEntityIds([$this->getGroupId()]);
        $this->collectionUpdaterRegistry->setOptionIds([]);
        $this->collectionUpdaterRegistry->setOptionValueIds([]);

        if (empty($this->options) && !$this->optionsInitialized) {
            $collection = $this->getGroupOptionsCollection();
            $this->joinProcessor->process($collection);

            /** @var Group\Option $option */
            foreach ($collection as $option) {
                $option->setProduct($this->getGroupOptionInstance()->getProduct());
                $this->addOption($option);
            }
            $this->optionsInitialized = true;
        }

        return $this->options;
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductCustomOptionInterface[] $options
     * @return $this
     */
    public function setOptions(array $options = null)
    {
        $this->options = $options;
        if (is_array($options) && empty($options)) {
            $this->setData('is_delete_options', true);
        }
        $this->optionsInitialized = true;

        return $this;
    }

    /**
     * Reset option initialization
     * @used in templates import
     *
     * @return $this
     */
    public function resetOptionInitialization()
    {
        $this->optionsInitialized = false;
        $this->options = null;
        $this->groupOptionInstance = null;
        return $this;
    }

    /**
     * Retrieve is a virtual product
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getIsVirtual()
    {
        return $this->getTypeInstance()->isVirtual($this);
    }

    /**
     * Add custom option information to product
     *
     * @param string $code Option code
     * @param mixed $value Value of the option
     * @param int|\Magento\Catalog\Model\Product $product Product ID
     * @return $this
     */
    public function addCustomOption($code, $value, $product = null)
    {
        $product = $product ? $product : $this;
        $option = $this->itemOptionFactory->create()->addData(
            ['product_id' => $product->getId(), 'product' => $product, 'code' => $code, 'value' => $value]
        );
        $this->_customOptions[$code] = $option;

        return $this;
    }

    /**
     * Sets custom options for the product
     *
     * @param array $options Array of options
     * @return void
     */
    public function setCustomOptions(array $options)
    {
        $this->_customOptions = $options;
    }

    /**
     * Get all custom options of the product
     *
     * @return array
     */
    public function getCustomOptions()
    {
        return $this->_customOptions;
    }

    /**
     * Get product custom option info
     *
     * @param string $code
     * @return array
     */
    public function getCustomOption($code)
    {
        if (isset($this->_customOptions[$code])) {
            return $this->_customOptions[$code];
        }

        return null;
    }

    /**
     * Checks if there custom option for this product
     *
     * @return bool
     */
    public function hasCustomOptions()
    {
        return (bool)count($this->_customOptions);
    }

    protected function _afterLoad()
    {
        $this->getProducts();
        parent::_afterLoad();
    }

    public function getOptionArray()
    {
        return $this->groupEntity->getOptionsAsArray($this);
    }

    /**
     * Retrieve option ids that will be deleted
     *
     * @return array
     */
    protected function getDeletedGroupOptions()
    {
        $deletedGroupOptions = [];

        if (!$this->getCanSaveCustomOptions()) {
            return $deletedGroupOptions;
        }

        $options = $this->getProductOptions();

        if (is_array($options)) {
            foreach ($options as $option) {
                if (isset($option['is_delete']) && $option['is_delete'] == '1') {
                    $deletedGroupOptions[] = $option['id'];
                }
            }
        }

        return $deletedGroupOptions;
    }

    /**
     * Retrieve array with the modified option properties : [{option_id} => [{property_name} => {property_value} , ...]]
     *
     * @return array
     */
    protected function getModifiedGroupOptions()
    {
        $modifiedGroupOptions = [];

        if (!$this->getCanSaveCustomOptions()) {
            return [];
        }

        $originalOptions = $this->getOrigData('product_options');
        $options = $this->getProductOptions();

        foreach ($options as $key => $option) {
            if (!empty($originalOptions[$key])) {
                $typeOld = !empty($originalOptions[$key]['type']) ? $originalOptions[$key]['type'] : null;
                $typeNew = !empty($option['type']) ? $option['type'] : null;

                if ($typeOld && $typeNew && $this->isSameOptionGroupType($typeOld, $typeNew)) {
                    if (!empty($originalOptions[$key]['optionValues'])) {
                        $originalOptions[$key]['values'] = $originalOptions[$key]['optionValues'];
                        unset($originalOptions[$key]['optionValues']);
                    } else {
                        $diff = array_diff($option, $originalOptions[$key]);
                    }

                    if (!empty($diff)) {
                        $modifiedGroupOptions[$key] = $diff;
                    }
                } else {
                    $deletedGroupOptions = $this->getDeletedGroupOptions();
                    $deletedGroupOptions[] = $key;
                    $this->setDeletedGroupOptions($deletedGroupOptions);
                }
            }
        }

        return $modifiedGroupOptions;
    }

    /**
     * Check product options and type options and save them, too
     *
     * @return void
     */
    public function beforeSave()
    {
        $this->setTypeHasOptions(false);
        $this->setTypeHasRequiredOptions(false);
        $hasOptions = false;
        $hasRequiredOptions = false;

        if ($this->getCanSaveCustomOptions()) {
            $options = $this->getProductOptions();

            if (is_array($options)) {
                $this->setIsCustomOptionChanged(true);

                foreach ($options as $option) {
                    $groupOptionInstance = $this->getGroupOptionInstance();
                    $groupOptionInstance->addOption($option);

                    if (!isset($option['is_delete']) || $option['is_delete'] != '1') {
                        $hasOptions = true;
                    }
                }

                foreach ($this->getGroupOptionInstance()->getOptions() as $option) {
                    if ($option['is_require'] == '1') {
                        $hasRequiredOptions = true;
                        break;
                    }
                }
            }
        }

        /**
         * Set true, if any
         * Set false, ONLY if options have been affected by Options tab and Type instance tab
         */
        if ($hasOptions || (bool)$this->getTypeHasOptions()) {
            $this->setHasOptions(true);
            if ($hasRequiredOptions || (bool)$this->getTypeHasRequiredOptions()) {
                $this->setRequiredOptions(true);
            } elseif ($this->canAffectOptions()) {
                $this->setRequiredOptions(false);
            }
        } elseif ($this->canAffectOptions()) {
            $this->setHasOptions(false);
            $this->setRequiredOptions(false);
        }

        parent::beforeSave();
    }

    /**
     *
     * {@inheritDoc}
     */
    public function beforeDelete()
    {
        $groupOptionIds = $this->getResource()->getGroupOptionIdsByGroupId($this->getId());
        if ($groupOptionIds) {
            /** @var \Magento\Catalog\Model\ResourceModel\Product\Option\Collection $productOptionCollection */
            $productOptionCollection = $this->productOptionCollectionFactory->create();
            $productOptionCollection->addFieldToFilter('group_option_id', $groupOptionIds);

            /** @var \Magento\Catalog\Model\Product\Option $option */
            foreach ($productOptionCollection as $option) {
                $option->delete();
            }
        }

        return parent::beforeDelete();
    }

    /**
     * Check/set if options can be affected when saving product
     * If value specified, it will be set.
     *
     * @param bool $value
     * @return bool
     */
    public function canAffectOptions($value = null)
    {
        if (null !== $value) {
            $this->canAffectOptions = (bool)$value;
        }

        return $this->canAffectOptions;
    }

    /**
     *
     * @return \MageWorx\OptionTemplates\Model\Group
     */
    public function afterSave()
    {
        /**
         * Group Options
         */
        $this->getGroupOptionInstance()->setProduct($this->convertGroupToProduct());
        $this->getGroupOptionInstance()->setOptions($this->options);
        $this->getGroupOptionInstance()->saveOptions();

        return parent::afterSave();
    }

    /**
     * Convert Group to Product entity for using option
     *
     * @return \Magento\Catalog\Model\Product
     */
    protected function convertGroupToProduct()
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $this->productFactory->create();
        $product->setData($this->getData())
            ->setId($this->getId())
            ->setStoreId(0);

        return $product;
    }


    /**
     * Set product relations to group, skip update part if it is needed
     *
     * @param $withUpdate
     * @return $this
     */
    public function setProductRelation($withUpdate = true)
    {
        $products = $this->getProductsIds();
        $oldProducts = $this->getProducts();

        if (!$products && $oldProducts) {
            $insert = [];
            $delete = $oldProducts;
            $update = [];
        } elseif (!$products) {
            $insert = [];
            $delete = [];
            $update = $oldProducts;
        } else {
            $insert = array_diff($products, $oldProducts);
            $delete = array_diff($oldProducts, $products);
            $update = array_intersect($products, $oldProducts);
        }

        if (!$withUpdate) {
            $update = [];
        }

        $this->setNewProductIds($insert);
        $this->setUpdProductIds($update);
        $this->setDelProductIds($delete);

        $productIds = array_unique(array_merge($update, $delete, $insert), SORT_NUMERIC);
        $this->setAffectedProductIds($productIds);

        return $this;
    }

    /**
     *
     * @param int $productId
     * @return $this
     */
    public function addProductRelation($productId)
    {
        if ($this->getResource()->addProductRelation($this->getId(), $productId)) {
            $products = $this->getData('products');
            array_push($products, $productId);
            $this->setData('products', $products);
        }

        return $this;
    }

    /**
     *
     * @param int $productId
     * @return $this
     */
    public function deleteProductRelation($productId)
    {
        if ($this->getResource()->deleteProductRelation($this->getGroupId(), $productId)) {
            $products = $this->getData('products');
            if (!$products || !is_array($products)) {
                return $this;
            }
            $key = array_search($productId, $products);
            if ($key !== false) {
                unset($products[$key]);
                $this->setData('products', $products);
            }
        }

        return $this;
    }

    /**
     *
     * @param string $typeOld
     * @param string $typeNew
     * @return bool
     */
    protected function isSameOptionGroupType($typeOld, $typeNew)
    {
        return ($this->getOptionGroupType($typeOld) == $this->getOptionGroupType($typeNew));
    }

    protected function getOptionGroupType($name)
    {
        foreach ($this->productOptionConfig->getAll() as $typeName => $data) {
            if (!empty($data['types'][$name])) {
                return $typeName;
            }
        }

        return null;
    }

    /**
     * Заглушка для продукта
     *
     * @return bool
     */
    public function isReadonly()
    {
        return false;
    }

    /**
     * Save group title
     *
     * @param string $title
     * @return void
     */
    public function saveTitle($title)
    {
        if (!$this->getGroupId()) {
            return;
        }
        $this->getResource()->saveTitle($this->getGroupId(), $title);
    }

    /**
     * Get template option in_group_id to option_id map
     *
     * @param array $templateMap
     * @param array $templateIds
     */
    public function processTemplateMap(&$templateMap, $templateIds)
    {
        $groupIds = [];
        foreach ($templateIds as $templateId) {
            if (!isset($templateMap[$templateId]) || !isset($templateMap[$templateId]['group_id'])) {
                continue;
            }
            $groupIds[$templateMap[$templateId]['group_id']] = $templateId;
        }

        if (!$groupIds) {
            return;
        }

        $data = $this->getResource()->getTemplateMapForImport($groupIds);

        if (!$data || !is_array($data)) {
            return;
        }

        foreach ($data as $datum) {
            $templateMap[$groupIds[$datum['group_id']]]['options'][$datum['option_sort_order']]['group_option_id'] = $datum['option_id'];
            if (isset($datum['value_id'])) {
                $templateMap[$groupIds[$datum['group_id']]]['options'][$datum['option_sort_order']]['values'][$datum['value_sort_order']]['group_value_id'] = $datum['value_id'];
            }
        }
    }

    /**
     * Add relation between group and product
     *
     * @param int $groupId
     * @param int $productId
     */
    public function addRelation($groupId, $productId)
    {
        $this->getResource()->addProductRelation($groupId, $productId);
    }
}
