<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionBase\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\ManagerInterface;
use MageWorx\OptionBase\Model\Product\Option\Attributes as OptionAttributes;
use MageWorx\OptionBase\Model\Product\Option\Value\Attributes as OptionValueAttributes;
use Magento\Catalog\Model\Product\Option;
use MageWorx\OptionTemplates\Model\Group as GroupInstance;
use Magento\Catalog\Model\Product as ProductInstance;
use MageWorx\OptionTemplates\Model\ResourceModel\Group as GroupResourceModel;
use MageWorx\OptionBase\Helper\Data as DataHelper;

class EntitySaveBefore implements ObserverInterface
{
    protected ManagerInterface $eventManager;
    protected OptionAttributes $optionAttributes;
    protected OptionValueAttributes $optionValueAttributes;
    protected Option $optionEntity;
    protected GroupResourceModel $groupResourceModel;
    protected DataHelper $dataHelper;

    /**
     * EntitySaveBefore constructor.
     *
     * @param ManagerInterface $eventManager
     * @param OptionValueAttributes $optionValueAttributes
     * @param OptionAttributes $optionAttributes
     * @param GroupResourceModel $groupResourceModel
     * @param Option $optionEntity
     * @param DataHelper $dataHelper
     */
    public function __construct(
        ManagerInterface $eventManager,
        OptionValueAttributes $optionValueAttributes,
        OptionAttributes $optionAttributes,
        GroupResourceModel $groupResourceModel,
        Option $optionEntity,
        DataHelper $dataHelper
    ) {
        $this->optionValueAttributes = $optionValueAttributes;
        $this->optionAttributes      = $optionAttributes;
        $this->eventManager          = $eventManager;
        $this->optionEntity          = $optionEntity;
        $this->groupResourceModel    = $groupResourceModel;
        $this->dataHelper            = $dataHelper;
    }

    /**
     *
     *
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        $processedOptions = [];
        $entity           = $observer->getObject() ?: $observer->getProduct();

        if (!$entity) {
            return $this;
        }
        $this->processUniqueTitle($entity);

        if (!$entity->getData('options')) {
            return $this;
        }

        foreach ($entity->getData('options') as $optionIndex => $option) {
            foreach ($this->optionAttributes->getData() as $attribute) {
                $option[$attribute->getName()] = $attribute->prepareDataBeforeSave($option);
            }

            if (isset($option['type'])
                && $this->optionEntity->getGroupByType($option['type']) === Option::OPTION_GROUP_SELECT
            ) {
                $processedValues = [];
                $values          = [];
                if (is_object($option) && $option->getData('values')) {
                    $values = $option->getData('values');
                } elseif (!empty($option['values']) && is_array($option['values'])) {
                    $values = $option['values'];
                }
                foreach ($values as $valueIndex => $value) {
                    if ($entity->getSku()) {
                        $value['is_default'] = $this->dataHelper->setIsDefaultAttrForLLPLogic(
                            $entity->getSku(),
                            $value
                        );
                    }

                    foreach ($this->optionValueAttributes->getData() as $valueAttribute) {
                        $value[$valueAttribute->getName()] = $valueAttribute->prepareDataBeforeSave($value);
                    }
                    $processedValues[$valueIndex] = $value;
                }
                $option['values'] = $processedValues;
            }
            $processedOptions[$optionIndex] = $option;
        }

        if ($observer->getObject()) {
            $entity->setData('options', $processedOptions);
            $entity->setData('product_options', $processedOptions);
            $entity->setOptions($processedOptions);
        } else {
            $entity->setOptions($processedOptions);
        }

        return $this;
    }

    /**
     * Set unique title if it is needed
     *
     * @param GroupInstance|ProductInstance $entity
     * @return void
     */
    protected function processUniqueTitle($entity)
    {
        if (!$entity->getIsUniqueTitleNeeded()) {
            return;
        }
        $this->groupResourceModel->findUniqueGroupTitle($entity);
    }
}
