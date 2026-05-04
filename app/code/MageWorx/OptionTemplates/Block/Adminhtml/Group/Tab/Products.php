<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionTemplates\Block\Adminhtml\Group\Tab;

use Magento\Backend\Block\Widget\Grid;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory as AttributeSetFactory;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\Product\Type as ProductType;
use MageWorx\OptionTemplates\Model\ResourceModel\Group;

class Products extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var AttributeSetFactory
     */
    protected $attributeSetFactory;

    /**
     * @var Group
     */
    protected $group;

    /**
     * @var BaseHelper
     */
    protected $baseHelper;

    /**
     * @var Status
     */
    private $status;

    /**
     * @var Visibility
     */
    private $visibility;

    /**
     * @var ProductType
     */
    protected $type;

    /**
     * @var string
     */
    protected $_template = 'MageWorx_OptionTemplates::widget/grid/extended.phtml';

    /**
     * Customers constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param ProductFactory $productFactory
     * @param AttributeSetFactory $attributeSetFactory
     * @param Group $group
     * @param BaseHelper $baseHelper
     * @param ProductType $type
     * @param Visibility $visibility
     * @param Status $status
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        ProductFactory $productFactory,
        AttributeSetFactory $attributeSetFactory,
        Group $group,
        BaseHelper $baseHelper,
        ProductType $type,
        Visibility $visibility,
        Status $status,
        array $data = []
    ) {
        $this->productFactory                 = $productFactory;
        $this->attributeSetFactory            = $attributeSetFactory;
        $this->group                          = $group;
        $this->baseHelper                     = $baseHelper;
        $this->type                           = $type;
        $this->visibility                     = $visibility;
        $this->status                         = $status;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('optiontemplates_group_products');
        $this->setDefaultSort('entity_id');
        $this->setDefaultFilter(['in_group' => 1]);
        $this->setUseAjax(true);
    }

    /**
     * @param Grid\Column $column
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _addColumnFilterToCollection($column)
    {
        if ($column->getId() == 'in_group') {
            $productIds = $this->getSelectedProducts();
            if (empty($productIds)) {
                $productIds = 0;
            }
            $linkField = 'entity_id';
            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter($linkField, ['in' => $productIds]);
            } elseif (!empty($productIds)) {
                $this->getCollection()->addFieldToFilter($linkField, ['nin' => $productIds]);
            }
        } else {
            parent::_addColumnFilterToCollection($column);
        }

        return $this;
    }

    /**
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareCollection()
    {
        $collection = $this->getProductCollection()->addAttributeToSelect(
            'name'
        )->addAttributeToSelect(
            'sku'
        )->addAttributeToSelect(
            'visibility'
        )->addAttributeToSelect(
            'status'
        )->addAttributeToSelect(
            'price'
        )->addAttributeToSelect(
            'attribute_set_id'
        )->addAttributeToSelect(
            'type_id'
        );

        $storeId = (int)$this->getRequest()->getParam('store', 0);
        if ($storeId > 0) {
            $collection->addStoreFilter($storeId);
        }
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * @return $this
     * @throws \Exception
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'in_group',
            [
                'type'             => 'checkbox',
                'name'             => 'in_group',
                'values'           => $this->getSelectedProducts(),
                'index'            => 'entity_id',
                'header_css_class' => 'col-select col-massaction',
                'column_css_class' => 'col-select col-massaction'
            ]
        );

        $this->addColumn(
            'entity_id',
            [
                'header' => __('ID'),
                'sortable' => true,
                'index' => 'entity_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );

        $this->addColumn('name', ['header' => __('Name'), 'index' => 'name']);

        $this->addColumn(
            'type',
            [
                'header' => __('Type'),
                'index' => 'type_id',
                'type' => 'options',
                'options' => $this->type->getOptionArray(),
                'header_css_class' => 'col-type',
                'column_css_class' => 'col-type'
            ]
        );

        $sets = $this->attributeSetFactory->create()->setEntityTypeFilter(
            $this->productFactory->create()->getResource()->getTypeId()
        )->load()->toOptionHash();
        $this->addColumn(
            'set_name',
            [
                'header' => __('Attribute Set'),
                'index' => 'attribute_set_id',
                'type' => 'options',
                'options' => $sets,
                'header_css_class' => 'col-attr-name',
                'column_css_class' => 'col-attr-name'
            ]
        );

        $this->addColumn('sku', ['header' => __('SKU'), 'index' => 'sku']);

        $this->addColumn(
            'price',
            [
                'header' => __('Price'),
                'type' => 'currency',
                'currency_code' => (string)$this->_scopeConfig->getValue(
                    \Magento\Directory\Model\Currency::XML_PATH_CURRENCY_BASE,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                ),
                'index' => 'price'
            ]
        );

        $this->addColumn(
            'visibility',
            [
                'header' => __('Visibility'),
                'index' => 'visibility',
                'type' => 'options',
                'options' => $this->visibility->getOptionArray(),
                'header_css_class' => 'col-visibility',
                'column_css_class' => 'col-visibility'
            ]
        );

        $this->addColumn(
            'status',
            [
                'header' => __('Status'),
                'index' => 'status',
                'type' => 'options',
                'options' => $this->status->getOptionArray()
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    protected function getProductCollection()
    {
        return $this->productFactory->create();
    }

    /**
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('mageworx_optiontemplates/group_product/grid', ['_current' => true]);
    }

    /**
     * @return array
     */
    protected function getSelectedProducts()
    {
        $params = $this->getRequest()->getParams();
        if (!empty($params['selected_products']) && is_array($params['selected_products'])) {
            $selectedProductIds = array_combine(
                array_values($params['selected_products']),
                array_values($params['selected_products'])
            );
        } else {
            if (!empty($params['group_id'])) {
                $productIds = $this->group->getProducts($params['group_id']);
                $selectedProductIds = array_combine(array_values($productIds), array_values($productIds));
            } else {
                $selectedProductIds = [];
            }
        }

        return $selectedProductIds;
    }

    /**
     * @return array
     */
    public function getSelectedProductsCount()
    {
        return count($this->getSelectedProducts());
    }

    /**
     * @return string
     */
    public function getProductCountWarningMessage()
    {
        return __('If you need to apply the template to a large number of products'
                  . ', '
                  . 'we recommend you to choose the products by small portions at once'
                  . ' '
                  . '(example, by 200-300 products) and apply the template.');
    }
}
