<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionDependency\Model\Attribute\Product;

use Magento\Framework\DataObjectFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Profiler;
use MageWorx\OptionDependency\Model\Attribute\Dependency as DependencyAttribute;
use MageWorx\OptionDependency\Model\Config;
use MageWorx\OptionDependency\Model\DependencyRules as DependencyRulesModel;
use MageWorx\OptionBase\Model\Product\AbstractProductAttribute;
use MageWorx\OptionBase\Api\ProductAttributeInterface;
use MageWorx\OptionBase\Helper\Data as BaseHelper;

class DependencyRules extends AbstractProductAttribute implements ProductAttributeInterface
{
    protected DependencyRulesModel $dependencyRulesModel;
    protected DependencyAttribute $dependencyAttribute;
    protected BaseHelper $baseHelper;

    /**
     * @param ResourceConnection $resource
     * @param DependencyRulesModel $dependencyRulesModel
     * @param DependencyAttribute $dependencyAttribute
     * @param BaseHelper $baseHelper
     * @param DataObjectFactory $dataObjectFactory
     */
    public function __construct(
        DependencyRulesModel $dependencyRulesModel,
        DependencyAttribute $dependencyAttribute,
        BaseHelper $baseHelper,
        ResourceConnection $resource,
        DataObjectFactory $dataObjectFactory
    ) {
        $this->dependencyRulesModel = $dependencyRulesModel;
        $this->dependencyAttribute  = $dependencyAttribute;
        $this->baseHelper           = $baseHelper;
        parent::__construct($resource, $dataObjectFactory);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return Config::KEY_DEPENDENCY_RULES;
    }

    /**
     * Collect product attribute data
     *
     * @param \MageWorx\OptionBase\Model\Entity\Group|\MageWorx\OptionBase\Model\Entity\Product $entity
     * @example of structure:
     * [{"conditions":[{"values":["484","485"],"type":"!eq","id":122}],"condition_type":"and","actions":{"hide":{"123":{"values":{"486":486},"id":123}}}}]
     * @return array
     */
    public function collectData($entity)
    {
        Profiler::start('APO1: MageWorx_OptionDependency::collectData');
        $this->entity = $entity;
        $data         = [];

        if ($entity->getType() !== 'product') {
            Profiler::stop('APO1: MageWorx_OptionDependency::collectData');
            return $data;
        }

        $linkField   = $entity->getDataObject()->getResource()->getLinkField();
        $linkFieldId = $entity->getDataObject()->getData($linkField);

        $dependencies  = [];
        $options       = $entity->getDataObject()->getData('merged_options') ?? [];
        $attributeData = $entity->getDataObject()->getData('mageworx_option_attributes');

        $entity->getDataObject()->setData('is_processing_dependency_rules', true);
        $nonCurrentTemplateDependencies = $this->dependencyAttribute->collectData($entity, $options);
        $entity->getDataObject()->setData('is_processing_dependency_rules', false);

        $currentTemplateDependencies = [];
        if (!empty($attributeData[$this->resource->getTableName(Config::TABLE_NAME)]['save'])) {
            $currentTemplateDependencies = $attributeData[$this->resource->getTableName(Config::TABLE_NAME)]['save'];
        }
        if (!empty($nonCurrentTemplateDependencies['save']) && $currentTemplateDependencies) {
            $rawDependencies = array_merge($nonCurrentTemplateDependencies['save'], $currentTemplateDependencies);
        } elseif (!empty($nonCurrentTemplateDependencies['save'])) {
            $rawDependencies = $nonCurrentTemplateDependencies['save'];
        } else {
            $rawDependencies = $currentTemplateDependencies;
        }
        if ($rawDependencies) {
            $dependencies = $this->dependencyRulesModel->getPreparedDependencies($rawDependencies);
        }

        $attributeValue = [];
        if ($dependencies && $options) {
            $attributeValue = $this->dependencyRulesModel->combineRules($dependencies, $options);
        }

        $data['save'][$linkFieldId][$this->getName()] = $this->baseHelper->jsonEncode($attributeValue);

        $data['delete'][$linkFieldId] = [
            'product_id' => $linkFieldId
        ];

        Profiler::stop('APO1: MageWorx_OptionDependency::collectData');
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
