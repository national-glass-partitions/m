<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionDependency\Model\Attribute;

use Magento\Framework\DataObjectFactory;
use Magento\Framework\App\ResourceConnection;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionBase\Model\Product\Option\AbstractAttribute;
use MageWorx\OptionDependency\Helper\Data as Helper;

class TitleId extends AbstractAttribute
{
    protected Helper $helper;

    public function __construct(
        ResourceConnection $resource,
        BaseHelper $baseHelper,
        Helper $helper,
        DataObjectFactory $dataObjectFactory
    ) {
        $this->helper = $helper;
        parent::__construct($resource, $baseHelper, $dataObjectFactory);
    }

    /**
     * {@inheritdoc}
     */
    public function prepareDataForFrontend($object)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function importTemplateMageOne($data)
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function importTemplateMageTwo($data)
    {
        return isset($data[$this->getName()]) ? $data[$this->getName()] : '';
    }
}
