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

class DependencyType extends AbstractAttribute
{
    const FIELD_MAGE_ONE_OPTIONS_IMPORT = '_custom_option_is_dependent';

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
        if (empty($data['is_dependent'])) {
            return 0;
        }
        return $data['is_dependent'] === '2' ? 1 : 0;
    }
}
