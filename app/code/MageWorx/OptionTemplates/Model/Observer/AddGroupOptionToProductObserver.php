<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionTemplates\Model\Observer;

use Magento\Catalog\Model\ResourceModel\Product\Option\CollectionFactory as ProductOptionCollectionFactory;
use Magento\Framework\Event\Observer;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionTemplates\Model\ResourceModel\Group\CollectionFactory as GroupCollectionFactory;
use MageWorx\OptionTemplates\Model\ProductAttributes as ProductAttributesEntity;
use MageWorx\OptionBase\Model\Product\Attributes as ProductAttributes;
use MageWorx\OptionBase\Model\ResourceModel\DataSaver;
use MageWorx\OptionTemplates\Model\ResourceModel\Group as GroupResourceModel;

/**
 * Observer class for add option groups to product
 */
class AddGroupOptionToProductObserver implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var  \Magento\Framework\Registry
     */
    protected $registry;

    /**
     *
     * @var \MageWorx\OptionTemplates\Model\OptionSaver
     */
    protected $optionSaver;

    /**
     *
     * @var GroupCollectionFactory
     */
    protected $groupCollectionFactory;

    /**
     *
     * @var ProductOptionCollectionFactory
     */
    protected $productOptionCollectionFactory;

    /**
     *
     * @var GroupResourceModel
     */
    protected $groupResourceModel;

    /**
     * @var BaseHelper
     */
    protected $baseHelper;

    /**
     * @var ProductAttributesEntity
     */
    protected $productAttributesEntity;

    /**
     * @var ProductAttributes
     */
    protected $productAttributes;

    /**
     * @var DataSaver
     */
    protected $dataSaver;

    /**
     * @var int
     */
    protected $productId = 0;

    /**
     *
     * @param \Magento\Framework\Registry $registry
     * @param \MageWorx\OptionTemplates\Model\OptionSaver $optionSaver
     * @param BaseHelper $baseHelper
     * @param GroupCollectionFactory $groupCollectionFactory
     * @param GroupResourceModel $groupResourceModel
     * @param ProductAttributes $productAttributes
     * @param ProductAttributesEntity $productAttributesEntity
     * @param ProductOptionCollectionFactory $productOptionCollectionFactory
     * @param DataSaver $dataSaver
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \MageWorx\OptionTemplates\Model\OptionSaver $optionSaver,
        GroupCollectionFactory $groupCollectionFactory,
        GroupResourceModel $groupResourceModel,
        BaseHelper $baseHelper,
        ProductAttributes $productAttributes,
        ProductAttributesEntity $productAttributesEntity,
        ProductOptionCollectionFactory $productOptionCollectionFactory,
        DataSaver $dataSaver
    ) {
        $this->registry                       = $registry;
        $this->optionSaver                    = $optionSaver;
        $this->groupCollectionFactory         = $groupCollectionFactory;
        $this->groupResourceModel             = $groupResourceModel;
        $this->baseHelper                     = $baseHelper;
        $this->productAttributesEntity              = $productAttributesEntity;
        $this->productAttributes              = $productAttributes;
        $this->productOptionCollectionFactory = $productOptionCollectionFactory;
        $this->dataSaver                      = $dataSaver;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $request         = $observer->getRequest();
        $this->productId = $request->getParam('id');
        $post            = $request->getPostValue();

        if ($this->_out($request)) {
            return;
        }

        $productOptions = [];
        if ($this->isPostContainProductOptions($post)) {
            $productOptions = $post['product']['options'];
        }

        if ($this->isPostContainGroups($post)) {
            $postGroupIds = $post['product']['option_groups'];
        } else {
            $post['product']['option_groups'] = [];
            $postGroupIds                     = [];
        }

        $keepOptionOnUnlink = !empty($post['product']['keep_options_on_unlink']);

        $productOptions    = $this->addGroupIdToValues($productOptions);
        $modProductOptions = $productOptions;

        /** @var \MageWorx\OptionTemplates\Model\ResourceModel\Group\Collection $collectionByProduct */
        $collectionByProduct = $this->groupCollectionFactory->create();
        $issetGroupIds       = $this->productId
            ? $collectionByProduct->addProductFilter($this->productId)->getAllIds()
            : [];
        $issetGroupIds       = array_map('strval', $issetGroupIds);

        $addedGroupIds   = array_diff($postGroupIds, $issetGroupIds);
        $deletedGroupIds = array_diff($issetGroupIds, $postGroupIds);

        $groupIds = array_merge($addedGroupIds, $deletedGroupIds);

        if ($groupIds) {
            $this->optionSaver->setIsTemplateSave(false);
            /** @var \MageWorx\OptionTemplates\Model\ResourceModel\Group\Collection $collection */
            $collection = $this->groupCollectionFactory->create()->addFieldToFilter('group_id', $groupIds);

            /** @var \MageWorx\OptionTemplates\Model\Group $group */
            foreach ($collection as $group) {
                if (in_array($group->getId(), $addedGroupIds)) {
                    $post['product']   = array_merge(
                        $post['product'],
                        $this->productAttributesEntity->getProductAttributesFromGroup($group)
                    );
                    $modProductOptions = $this->optionSaver->addNewOptionProcess($modProductOptions, '', $group);
                }
                if (in_array($group->getId(), $deletedGroupIds)) {
                    if ($keepOptionOnUnlink) {
                        $modProductOptions = $this->optionSaver->unassignOptions($modProductOptions, $group);
                        $group->deleteProductRelation($this->productId);
                    } else {
                        $modProductOptions = $this->optionSaver->deleteOptionProcess(
                            $modProductOptions,
                            $group
                        );
                    }
                }
            }

            $notRemovedGroupIds = array_diff($issetGroupIds, $deletedGroupIds);
            $this->applyProductAttributePriorityValue($post, array_merge($notRemovedGroupIds, $addedGroupIds));
        }

        $registryIds = [
            'productId'   => $this->productId,
            'newGroupIds' => $addedGroupIds,
            'delGroupIds' => $deletedGroupIds,
        ];

        $this->registry->register('mageworx_optiontemplates_relation_data', $registryIds, true);

        $modProductOptions = $this->applyUseDefaults($modProductOptions, $post);

        $post['product']['options'] = $modProductOptions;
        $request->setPostValue($post);
    }

    /**
     * Apply product attributes
     *
     * @param array $post
     * @param array $groupIds
     * @return void
     */
    protected function applyProductAttributePriorityValue(&$post, $groupIds)
    {
        $attributes = $this->productAttributes->getData();
        if (!$attributes || !is_array($attributes)) {
            return;
        }

        /** @var \MageWorx\OptionBase\Api\ProductAttributeInterface $attribute */
        foreach ($attributes as $attribute) {
            $priorityValue = $attribute->getPriorityValue();
            if (!isset($priorityValue)) {
                continue;
            }
            if ($this->groupResourceModel->hasPriorityValue(
                $attribute->getName(),
                $priorityValue,
                $groupIds
            )) {
                $post['product'] = array_merge(
                    $post['product'],
                    [$attribute->getName() => $priorityValue]
                );
            }
        }
    }

    /**
     * Apply use_default for options, option/value titles
     *
     * @param array $modProductOptions
     * @param array $post
     * @return array
     */
    protected function applyUseDefaults($modProductOptions, $post)
    {
        $optionTypeIds = [];

        foreach ($modProductOptions as $optionKey => $optionData) {
            if (!empty($post['options_use_default'])) {
                $useDefaults = is_array($post['options_use_default']) ? $post['options_use_default'] : [];
                foreach ($useDefaults as $useDefaultOptionId => $useDefaultOptionData) {
                    if (!isset($optionData['option_id']) || $useDefaultOptionId != $optionData['option_id']) {
                        continue;
                    }
                    $modProductOptions[$optionKey]['is_use_default'] = empty($useDefaultOptionData['title']) ? 0 : 1;

                    if (empty($optionData['values'])) {
                        continue;
                    }
                    $values = $optionData['values'];
                    $this->processValues($optionData, $values, $useDefaultOptionData, $optionTypeIds);
                    $modProductOptions[$optionKey]['values'] = $values;
                }
            } else {
                if (empty($optionData['values'])) {
                    continue;
                }
                $values = $optionData['values'];
                foreach ($values as $valueKey => $value) {
                    if (isset($value['option_type_id']) && !$this->isImportedOption($optionData)) {
                        $optionTypeIds[] = "'" . $value['option_type_id'] . "'";
                    }
                }
                $modProductOptions[$optionKey]['values'] = $values;
            }
        }

        return $modProductOptions;
    }

    /**
     * Process option values: set option_type_id to null, set necessary value for is_use_default
     *
     * @param array $optionData
     * @param array $values
     * @param array $useDefaultOptionData
     * @param array $optionTypeIds
     * @return void
     */
    protected function processValues($optionData, &$values, $useDefaultOptionData, &$optionTypeIds)
    {
        foreach ($values as $valueKey => $value) {

            $useDefaultsOptionValues = !empty($useDefaultOptionData['values'])
                ? $useDefaultOptionData['values']
                : [];
            foreach ($useDefaultsOptionValues as $useDefaultOptionValueId => $useDefaultOptionValueData) {
                if (!isset($values[$valueKey]['option_type_id']) ||
                    $useDefaultOptionValueId != $values[$valueKey]['option_type_id']
                ) {
                    continue;
                }
                $values[$valueKey]['is_use_default'] = empty($useDefaultOptionValueData['title']) ? 0 : 1;
            }

            if (isset($value['option_type_id']) && !$this->isImportedOption($optionData)) {
                $optionTypeIds[] = "'" . $value['option_type_id'] . "'";
            }
        }
    }

    /**
     * Add group id to values
     *
     * @param array $productOptions
     * @return array
     */
    protected function addGroupIdToValues($productOptions)
    {
        foreach ($productOptions as $optionIndex => $productOption) {
            if (empty($productOption['group_id']) || empty($productOption['values'])) {
                continue;
            }
            foreach ($productOption['values'] as $valueIndex => $valueData) {
                $productOptions[$optionIndex]['values'][$valueIndex]['group_id'] = $productOption['group_id'];
            }
        }
        return $productOptions;
    }

    /**
     * Check if go out
     *
     * @param $request
     * @return bool
     */
    protected function _out($request)
    {
        if (!in_array($request->getFullActionName(), $this->_getAvailableActions())) {
            return true;
        }

        $isCanSaveOptions = isset($request->getPost('product')['affect_product_custom_options']);

        if (!$isCanSaveOptions) {
            return true;
        }

        return false;
    }

    /**
     * Retrieve list of available actions
     *
     * @return array
     */
    protected function _getAvailableActions()
    {
        return ['catalog_product_save'];
    }

    /**
     * Check if post contains product options
     *
     * @param $post array
     * @return bool
     */
    protected function isPostContainProductOptions($post)
    {
        if (isset($post['product']['options']) && is_array($post['product']['options'])) {
            return true;
        }
        return false;
    }

    /**
     * Check if post contains groups
     *
     * @param $post array
     * @return bool
     */
    protected function isPostContainGroups($post)
    {
        if (!isset($post['product']['option_groups']) ||
            !is_array($post['product']['option_groups']) ||
            (count($post['product']['option_groups']) == 1 && $post['product']['option_groups'][0] == 'none')
        ) {
            return false;
        }
        return true;
    }

    /**
     * Check if option is imported from another product
     *
     * @param array $optionData
     * @return bool
     */
    protected function isImportedOption($optionData)
    {
        return $this->productId && isset($optionData['product_id']) && $this->productId != $optionData['product_id'];
    }
}
