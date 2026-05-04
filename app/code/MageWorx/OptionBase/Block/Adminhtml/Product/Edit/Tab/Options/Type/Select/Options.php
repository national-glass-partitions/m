<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionBase\Block\Adminhtml\Product\Edit\Tab\Options\Type\Select;

/**
 * Class Options. Collect options and it titles from All modules (like OptionInventory)
 * to paste it into Container.
 * @package MageWorx\OptionBase\Block\Adminhtml\Product\Edit\Tab\Options\Type\Select
 */
class Options extends \Magento\Framework\View\Element\Template
{
    /**
     * All options titles formatted to html
     *
     * @var string
     */
    protected string $collectedTitleFields;

    /**
     * All options formatted to html
     *
     * @var string
     */
    protected string $collectedOptionFields;

    /**
     * $options collected array options titles from child modules like OptionInventory
     * @var array
     */
    protected array $titles = [];

    /**
     * $options collected array options from child modules like OptionInventory
     * @var array
     */
    protected array $options = [];

    /**
     * Options constructor.
     * @param array $titles
     * @param array $options
     */
    public function __construct(
        $titles = [],
        $options = []
    ) {

        $this->titles = $titles;
        $this->options = $options;
    }

    /**
     * Retrieve options titles
     *
     * @return string
     */
    public function getTitlesHtml()
    {
        $this->collectTitleFields();

        return $this->collectedTitleFields;
    }

    /**
     * Retrieve options
     *
     * @return string
     */
    public function getOptionsHtml()
    {
        $this->collectOptionFields();

        return $this->collectedOptionFields;
    }

    /**
     * Collect all options titles to one html string
     *
     * @return string
     */
    protected function collectTitleFields()
    {
        foreach ($this->titles as $title) {
            $this->collectedTitleFields .= $title->toHtml();
        }
    }

    /**
     * Collect all option fields to one html string
     *
     * @return string (html)
     */
    protected function collectOptionFields()
    {
        foreach ($this->options as $option) {
            $this->collectedOptionFields .= $option->toHtml();
        }
    }
}
