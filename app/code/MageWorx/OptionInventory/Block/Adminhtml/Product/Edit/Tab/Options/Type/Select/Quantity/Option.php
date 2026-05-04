<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionInventory\Block\Adminhtml\Product\Edit\Tab\Options\Type\Select\Quantity;

/**
 * Class Option.
 * This class retrieve 'Quantity' option html from $_template.
 *
 * @package MageWorx\OptionInventory\Block\Adminhtml\Product\Edit\Tab\Options\Type\Select\Quantity
 */
class Option extends \Magento\Catalog\Block\Adminhtml\Product\Edit\Tab\Options\Type\AbstractType
{
    /**
     * Option html
     *
     * @var string
     */
    protected $_template = 'catalog/product/edit/options/type/select/quantity/option.phtml';

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
