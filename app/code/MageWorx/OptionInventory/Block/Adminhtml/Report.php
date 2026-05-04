<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionInventory\Block\Adminhtml;

/**
 * Class Report. Report grid block.
 * @package MageWorx\OptionInventory\Block\Adminhtml
 */
class Report extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Modify header labels
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_controller = 'optioninventory_report';
        $this->_headerText = __('Option Inventory Report');
        parent::_construct();
    }
}
