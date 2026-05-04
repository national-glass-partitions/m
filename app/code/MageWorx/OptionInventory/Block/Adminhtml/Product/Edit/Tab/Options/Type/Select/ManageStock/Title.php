<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionInventory\Block\Adminhtml\Product\Edit\Tab\Options\Type\Select\ManageStock;

/**
 * Class Option.
 * This class retrieve 'Manage Stock' option title html from $_template.
 *
 * @package MageWorx\OptionInventory\Block\Adminhtml\Product\Edit\Tab\Options\Type\Select\ManageStock
 */
class Title extends \Magento\Catalog\Block\Adminhtml\Product\Edit\Tab\Options\Type\AbstractType
{
    /**
     * Option title html
     *
     * @var string
     */
    protected $_template = 'catalog/product/edit/options/type/select/manage-stock/title.phtml';

    /**
     * Class constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        return parent::_prepareLayout();
    }
}
