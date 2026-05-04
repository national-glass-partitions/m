<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionDependency\Model\Attribute\Product;

use Magento\Framework\DataObjectFactory;
use Magento\Framework\App\ResourceConnection;
use MageWorx\OptionDependency\Model\Config;
use MageWorx\OptionDependency\Model\HiddenDependents as HiddenDependentsModel;
use MageWorx\OptionBase\Model\Product\AbstractProductAttribute;
use MageWorx\OptionBase\Api\ProductAttributeInterface;
use MageWorx\OptionBase\Helper\Data as BaseHelper;

class HiddenDependents extends AbstractProductAttribute implements ProductAttributeInterface
{
    protected HiddenDependentsModel $hiddenDependentsModel;
    protected BaseHelper $baseHelper;

    /**
     * @param ResourceConnection $resource
     * @param HiddenDependentsModel $hiddenDependentsModel
     * @param BaseHelper $baseHelper
     * @param DataObjectFactory $dataObjectFactory
     */
    public function __construct(
        HiddenDependentsModel $hiddenDependentsModel,
        BaseHelper $baseHelper,
        ResourceConnection $resource,
        DataObjectFactory $dataObjectFactory
    ) {
        $this->hiddenDependentsModel = $hiddenDependentsModel;
        $this->baseHelper            = $baseHelper;
        parent::__construct($resource, $dataObjectFactory);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return Config::KEY_HIDDEN_DEPENDENTS;
    }

    /**
     * Collect product attribute data
     *
     * @param \MageWorx\OptionBase\Model\Entity\Group|\MageWorx\OptionBase\Model\Entity\Product $entity
     * @example of structure:
     * {"hidden_options":[123],"hidden_values":[486,487],"preselected_values":{"582":["2334","2336"],"584":["2340"]}}
     * @return array
     */
    public function collectData($entity)
    {
        $this->entity = $entity;
        $data         = [];

        if ($entity->getType() !== 'product') {
            return $data;
        }

        $linkField   = $entity->getDataObject()->getResource()->getLinkField();
        $linkFieldId = $entity->getDataObject()->getData($linkField);

        $productAttributeData = $entity->getDataObject()->getData('mageworx_product_attributes');
        if (empty($productAttributeData[Config::KEY_DEPENDENCY_RULES]['save'][$linkFieldId][Config::KEY_DEPENDENCY_RULES])) {
            return $data;
        }
        $dependencyRulesJson = $productAttributeData[Config::KEY_DEPENDENCY_RULES]['save'][$linkFieldId][Config::KEY_DEPENDENCY_RULES];
        $dependencyRules     = $this->baseHelper->jsonDecode($dependencyRulesJson);

        $mageworxOptionAttributes = $entity->getDataObject()->getData('mageworx_option_attributes');
        $defaultsTableName        = 'mageworx_optionfeatures_option_type_is_default';
        $defaults                 = [];
        if (!empty($mageworxOptionAttributes[$this->resource->getTableName($defaultsTableName)]['save'])) {
            $defaults = $mageworxOptionAttributes[$this->resource->getTableName($defaultsTableName)]['save'];
        }

        $options        = $entity->getDataObject()->getData('merged_options');
        $attributeValue = $this->hiddenDependentsModel->getHiddenDependents($options, $dependencyRules, $defaults);
        if (isset($attributeValue)) {
            $data['save'][$linkFieldId][$this->getName()] = $this->baseHelper->jsonEncode($attributeValue);
        };

        $data['delete'][$linkFieldId] = [
            'product_id' => $linkFieldId
        ];

        return $data;
    }

    /**
     * Flag to check if attribute should be skipped during Magento 2 export
     *
     * @return bool
     */
    public function shouldSkipExportMageTwo()
    {
        return true;
    }
}
