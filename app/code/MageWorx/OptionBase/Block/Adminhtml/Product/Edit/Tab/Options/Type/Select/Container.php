<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionBase\Block\Adminhtml\Product\Edit\Tab\Options\Type\Select;

use Magento\Framework\View\Element\Template;
use \MageWorx\OptionBase\Block\Adminhtml\Product\Edit\Tab\Options\Type\Select\Options as SelectOptions;
use \Magento\Framework\View\Element\Template\Context;

/**
 * Class Container. Get options and it titles and add to base Magento options template.
 * @package MageWorx\OptionBase\Block\Adminhtml\Product\Edit\Tab\Options\Type\Select
 */
class Container extends Template
{

    protected $_template = 'catalog/product/edit/options/type/select/container.phtml';

    protected Options $options;

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

    /**
     * Container constructor.
     *
     * @param Options $options
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        SelectOptions $options,
        Context $context,
        array $data = []
    ) {
        $this->options = $options;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve all options title.
     *
     * @return html
     */
    public function getTitlesHtml()
    {
        return $this->options->getTitlesHtml();
    }

    /**
     * Retrieve all options element.
     *
     * @return html
     */
    public function getOptionsHtml()
    {
        return $this->options->getOptionsHtml();
    }
}
