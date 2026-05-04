<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionTemplates\Block\Adminhtml\Group;

use MageWorx\OptionTemplates\Model\ResourceModel\Group;

class Products extends \Magento\Backend\Block\Template
{
    /**
     * Block template
     *
     * @var string
     */
    protected $_template = 'group/edit/products.phtml';

    /**
     * Block Grid
     */
    protected $blockGrid;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $jsonEncoder;

    /**
     * @var Group
     */
    protected $group;

    /**
     * AssignCustomers constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param Group $group
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        Group $group,
        array $data = []
    ) {
        $this->registry                       = $registry;
        $this->jsonEncoder                    = $jsonEncoder;
        $this->group                          = $group;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve instance of grid block
     *
     * @return \Magento\Framework\View\Element\BlockInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getBlockGrid()
    {
        if (null === $this->blockGrid) {
            $this->blockGrid = $this->getLayout()->createBlock(
                \MageWorx\OptionTemplates\Block\Adminhtml\Group\Tab\Products::class,
                'group.products.grid'
            );
        }

        return $this->blockGrid;
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getGridHtml()
    {
        return $this->getBlockGrid()->toHtml();
    }

    /**
     * @return string
     */
    public function getProductsJson()
    {
        $params = $this->getRequest()->getParams();
        if (!empty($params['group_id'])) {
            $productIds = $this->group->getProducts($params['group_id']);
            $selectedProductIds = array_combine(array_values($productIds), array_values($productIds));
        } else {
            $selectedProductIds = [];
        }
        return $this->jsonEncoder->encode($selectedProductIds);
    }

    /**
     * @return string
     */
    public function getFieldId()
    {
        return 'in_group_products';
    }

    /**
     * Get block name
     *
     * @return string
     */
    public function getNameInLayout()
    {
        return $this->_nameInLayout === null ? '' : $this->_nameInLayout;
    }
}
