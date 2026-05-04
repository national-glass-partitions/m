<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionBase\Observer;

use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Model\ResourceModel\Product\Option\Value\Collection as OptionValueCollection;
use MageWorx\OptionBase\Model\ProductAttributes as ProductAttributesEntity;
use \MageWorx\OptionBase\Model\Product\Attributes as ProductAttributes;
use \MageWorx\OptionBase\Model\Product\Option\Attributes as OptionAttributes;
use \MageWorx\OptionBase\Model\Product\Option\Value\Attributes as OptionValueAttributes;
use \MageWorx\OptionBase\Model\Entity\Product as ProductEntity;
use \MageWorx\OptionBase\Helper\Data as Helper;
use MageWorx\OptionBase\Model\AttributeSaver;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use MageWorx\OptionBase\Model\ResourceModel\DataSaver;
use MageWorx\OptionTemplates\Model\ResourceModel\Group as GroupResourceModel;

class ApplyAttributesOnProduct implements ObserverInterface
{
    protected OptionValueCollection $optionValueCollection;
    protected ProductAttributes $productAttributes;
    protected OptionAttributes $optionAttributes;
    protected OptionValueAttributes $optionValueAttributes;
    protected ProductEntity $productEntity;
    protected Product $productModel;
    protected GroupResourceModel $groupResourceModel;
    protected Helper $helper;
    protected AttributeSaver $attributeSaver;
    protected ResourceConnection $resource;
    protected MessageManager $messageManager;
    protected Logger $logger;
    protected DataSaver $dataSaver;

    /**
     * Product options
     *
     * @var array
     */
    protected array $options = [];

    /**
     * Product ID
     *
     * @var int|null
     */
    protected $productId = null;

    public function __construct(
        OptionValueCollection $optionValueCollection,
        ProductAttributes $productAttributes,
        OptionAttributes $optionAttributes,
        OptionValueAttributes $optionValueAttributes,
        Product $productModel,
        ProductEntity $productEntity,
        GroupResourceModel $groupResourceModel,
        Helper $helper,
        ResourceConnection $resource,
        Logger $logger,
        MessageManager $messageManager,
        AttributeSaver $attributeSaver,
        DataSaver $dataSaver
    ) {
        $this->optionValueCollection = $optionValueCollection;
        $this->productAttributes     = $productAttributes;
        $this->optionAttributes      = $optionAttributes;
        $this->optionValueAttributes = $optionValueAttributes;
        $this->productModel          = $productModel;
        $this->groupResourceModel    = $groupResourceModel;
        $this->productEntity         = $productEntity;
        $this->helper                = $helper;
        $this->resource              = $resource;
        $this->logger                = $logger;
        $this->messageManager        = $messageManager;
        $this->attributeSaver        = $attributeSaver;
        $this->dataSaver             = $dataSaver;
    }

    /**
     * Save product, option and value APO attributes on product
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $product = $observer->getProduct();
        if (!$product) {
            return;
        }
        $isAfterTemplate = $observer->getIsAfterTemplate();

        $this->initProductId($observer);
        $this->initOptions($observer);
        $product->setData('merged_options', $this->options);

        $this->productEntity->setDataObject($product);
        $this->productEntity->setIsAfterTemplate($isAfterTemplate);

        $optionValueAttributes = $this->optionValueAttributes->getData();
        $this->collectAttributeData($optionValueAttributes);
        $optionAttributes = $this->optionAttributes->getData();
        $this->collectAttributeData($optionAttributes);

        $this->productEntity->getDataObject()
                            ->setData('mageworx_option_attributes', $this->attributeSaver->getAttributeData());
        $this->collectProductAttributeData();

        if ($isAfterTemplate) {
            return;
        }

        // save product data if it is not from template
        $this->resource->getConnection()->beginTransaction();
        try {
            $collectedData = $this->attributeSaver->getAttributeData();
            $this->attributeSaver->deleteOldAttributeData($collectedData, 'product');
            foreach ($collectedData as $tableName => $dataArray) {
                if (empty($dataArray['save'])) {
                    continue;
                }
                $this->dataSaver->insertMultipleData($tableName, $dataArray['save']);
            }
            $this->resource->getConnection()->commit();
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __("Something went wrong while saving product's APO attributes")
            );
            $this->logger->critical($e->getMessage());
            $this->resource->getConnection()->rollBack();
        }
        $this->attributeSaver->clearAttributeData();
    }

    /**
     * Apply product attributes
     *
     * @return void
     */
    protected function collectProductAttributeData()
    {
        $productAttributes = $this->productAttributes->getData();
        if (!$productAttributes || !is_array($productAttributes)) {
            return;
        }

        $data = [];
        foreach ($productAttributes as $productAttribute) {
            if ($this->productEntity->getIsAfterTemplate()) {
                $this->processPriorityValue($productAttribute);
            }

            $attributeData = $productAttribute->collectData($this->productEntity);
            if (!$attributeData) {
                continue;
            }

            $productAttributes = $this->productEntity->getDataObject()->getData('mageworx_product_attributes');
            if (!$productAttributes) {
                $productAttributes = [];
            }
            $productAttributes = array_merge($productAttributes, [$productAttribute->getName() => $attributeData]);
            $this->productEntity->getDataObject()->setData('mageworx_product_attributes', $productAttributes);

            if (!empty($attributeData['delete'])) {
                foreach ($attributeData['delete'] as $attributeDatum) {
                    $data['delete'][] = $attributeDatum;
                }
            }

            if (empty($attributeData['save'])) {
                continue;
            }
            foreach ($attributeData['save'] as $attributeDatum) {
                if (!isset($data['save'][$this->productId])) {
                    $data['save'][$this->productId] = $attributeDatum;
                } else {
                    $data['save'][$this->productId] = array_merge(
                        $data['save'][$this->productId],
                        $attributeDatum
                    );
                }
            }
            $data['save'][$this->productId]['product_id'] = $this->productId;
        }

        $tableName = $this->resource->getTableName(ProductAttributesEntity::TABLE_NAME);
        $this->attributeSaver->addAttributeData($tableName, $data);
    }

    /**
     * Process setting priority value if necessary
     *
     * @param array $productAttribute
     * @return void
     */
    protected function processPriorityValue($productAttribute)
    {
        $priorityValue = $productAttribute->getPriorityValue($productAttribute->getName());
        if (!isset($priorityValue)) {
            return;
        }
        $groupIds = $this->groupResourceModel->getGroupIds($this->productId);
        if ($this->groupResourceModel->hasPriorityValue(
            $productAttribute->getName(),
            $priorityValue,
            $groupIds
        )) {
            $this->productEntity->getDataObject()->setData(
                $productAttribute->getName(),
                $priorityValue
            );
        }
    }

    /**
     * Collect attribute data
     *
     * @param array $attributes
     * @return void
     */
    protected function collectAttributeData($attributes)
    {
        foreach ($attributes as $attribute) {
            $data = [];
            if (!$attribute->hasOwnTable()) {
                continue;
            }
            $attributeItemData = $attribute->collectData($this->productEntity, $this->options);
            if (!$attributeItemData) {
                continue;
            }
            $tableName = $this->resource->getTableName($attribute->getTableName('product'));

            if (!empty($attributeItemData['save'])) {
                foreach ($attributeItemData['save'] as $attributeItemDataItem) {
                    $data['save'][] = $attributeItemDataItem;
                }
            }
            if (!empty($attributeItemData['delete'])) {
                foreach ($attributeItemData['delete'] as $attributeItemDataItem) {
                    $data['delete'][] = $attributeItemDataItem;
                }
            }
            $this->attributeSaver->addAttributeData($tableName, $data);
        }
    }

    /**
     * @param Observer $observer
     * @return void
     */
    protected function initProductId($observer)
    {
        $this->productId = $observer->getEvent()->getProduct()->getData($this->helper->getLinkField());
    }

    /**
     * @param Observer $observer
     * @return void
     */
    protected function initOptions($observer)
    {
        $currentOptions = $observer->getProduct()->getOptions();
        if ($observer->getProduct()->getIsAfterTemplateSave()) {
            $this->options = [];
            if (empty($currentOptions)) {
                return;
            }
            foreach ($currentOptions as $currentOption) {
                if (!empty($currentOption['is_delete'])) {
                    continue;
                }
                $this->options[] = $currentOption;
            }
        } else {
            $savedOptions = $this->productModel->load($observer->getProduct()->getId())->getOptions();

            $currentOptions = $this->helper->beatifyOptions($currentOptions);
            $savedOptions   = $this->helper->beatifyOptions($savedOptions);

            $this->options = $this->mergeArrays($currentOptions, $savedOptions);

            /* temporary fix^ see APO issues 1076 */
            if (empty($this->options)) {
                $origOptions = $observer->getProduct()->getOrigData('options');
                $origOptions = $this->helper->beatifyOptions($origOptions);

                $data     = [];
                $optionId = 'option_id';
                foreach ($origOptions as $origOption) {
                    $data['delete'][] = [$optionId => $origOption[$optionId]];

                }

                if (empty($data)) {
                    return;
                }

                $this->attributeSaver->deleteOptionAttributesProcess(
                    $this->resource->getTableName('mageworx_option_dependency'),
                    'product',
                    $data
                );
            }
        }
    }

    /**
     * Merge current and saved arrays
     *
     * @param array $current
     * @param array $saved
     * @return array
     */
    protected function mergeArrays($current, $saved)
    {
        foreach ($current as $currentOption) {
            if (!empty($currentOption['is_delete'])) {
                continue;
            }
            $currentOptionId       = $currentOption['option_id'];
            $currentOptionRecordId = $currentOption['record_id'] ?? $currentOption['option_id'];
            $isNewFromGroupOption  = false;

            $currentOptionAttributes = [];
            $optionAttributes        = $this->optionAttributes->getData();
            foreach ($optionAttributes as $optionAttribute) {
                $currentOptionAttributes[] = $optionAttribute->getName();
            }
            // set data to option $saved
            $savedOptionKey = $this->helper->searchArray('option_id', $currentOptionId, $saved);
            if ($savedOptionKey === null) {
                continue;
            }
            $saved[$savedOptionKey]['record_id'] = $currentOptionRecordId;
            foreach ($currentOptionAttributes as $currentOptionAttribute) {
                if (!isset($currentOption[$currentOptionAttribute])) {
                    continue;
                }
                $saved[$savedOptionKey][$currentOptionAttribute] = $currentOption[$currentOptionAttribute];
            }

            if (!empty($currentOption['is_use_default'])) {
                $saved[$savedOptionKey]['is_use_default'] = 1;
            }
            if (!empty($currentOption['group_id'])) {
                $saved[$savedOptionKey]['group_id'] = $currentOption['group_id'];
            }

            if (!empty($currentOption['group_option_id'])
                && in_array($currentOption['group_option_id'], $this->attributeSaver->getNewGroupOptionIds())
            ) {
                $saved[$savedOptionKey]['need_to_process_dependency'] = true;
                $isNewFromGroupOption                                 = true;
            }

            $currentValues = $currentOption['values'] ?? [];
            foreach ($currentValues as $currentValue) {
                $currentValueSortOrder = $currentValue['sort_order'];
                $currentValueRecordId  = $currentValue['record_id'] ?? $currentValue['option_type_id'];

                $currentValueAttributes = [];
                $valueAttributes        = $this->optionValueAttributes->getData();
                foreach ($valueAttributes as $valueAttribute) {
                    $currentValueAttributes[] = $valueAttribute->getName();
                }

                // set data to option $saved
                $savedValueKey = $this->helper->searchArray(
                    'sort_order',
                    $currentValueSortOrder,
                    $saved[$savedOptionKey]['values']
                );
                if ($savedValueKey === null) {
                    continue;
                }

                $optionValue = &$saved[$savedOptionKey]['values'][$savedValueKey];
                if (!empty($currentValue['group_id'])) {
                    $optionValue['group_id'] = $currentValue['group_id'];
                }
                $optionValue['record_id'] = $currentValueRecordId;
                $optionValue['option_id'] = $saved[$savedOptionKey]['option_id'];
                foreach ($currentValueAttributes as $currentValueAttribute) {
                    if (!isset($currentValue[$currentValueAttribute])) {
                        continue;
                    }
                    $optionValue[$currentValueAttribute] = $currentValue[$currentValueAttribute];
                }
                if (!empty($currentValue['is_use_default'])) {
                    $optionValue['is_use_default'] = 1;
                }
                if ($isNewFromGroupOption) {
                    $optionValue['need_to_process_dependency'] = true;
                }
            }
        }

        return $saved;
    }
}
