<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionBase\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use MageWorx\OptionBase\Api\ValidatorInterface;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use Magento\Catalog\Api\Data\ProductCustomOptionInterfaceFactory as OptionFactory;
use MageWorx\OptionBase\Model\ResourceModel\DataSaver;
use MageWorx\OptionBase\Model\OptionSaver\Option as OptionDataCollector;
use MageWorx\OptionTemplates\Model\Group as GroupModel;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use MageWorx\OptionBase\Model\ResourceModel\CollectionUpdaterRegistry;
use Magento\Framework\Exception\LocalizedException;
use MageWorx\OptionBase\Model\ResourceModel\Option as MageworxOptionResource;
use MageWorx\OptionImportExport\Model\Config\Source\MigrationMode;
use MageWorx\OptionBase\Model\ValidationResolver as ValidationResolver;

class OptionHandler
{
    protected OptionFactory $optionFactory;
    protected BaseHelper $baseHelper;
    protected OptionDataCollector $optionDataCollector;
    protected MageworxOptionResource $mageworxOptionResource;
    protected DataSaver $dataSaver;
    protected ManagerInterface $eventManager;
    protected ResourceConnection $resource;
    protected array $optionData;
    protected GroupModel $groupModel;
    protected array $optionsToDelete;
    protected ProductCollectionFactory $productCollectionFactory;
    protected CollectionUpdaterRegistry $collectionUpdaterRegistry;
    protected array $productsWithRequiredOptions = [];
    protected string $linkField = '';
    protected array $currentIncrementIds = [];
    protected array $preparedOptions = [];
    protected int $sortOrderCounter;
    protected ValidationResolver $validationResolver;
    protected array $productsHasNoOptions = [];
    protected array $productsHasNoRequiredOptions = [];
    protected array $productsWithMageWorxIsRequire = [];

    /**
     * @param OptionFactory $optionFactory
     * @param BaseHelper $baseHelper
     * @param OptionDataCollector $optionDataCollector
     * @param DataSaver $dataSaver
     * @param ManagerInterface $eventManager
     * @param ResourceConnection $resource
     * @param ProductCollectionFactory $productCollectionFactory
     * @param CollectionUpdaterRegistry $collectionUpdaterRegistry
     * @param GroupModel $groupModel
     * @param MageworxOptionResource $mageworxOptionResource
     * @param ValidationResolver $validationResolver
     */
    public function __construct(
        OptionFactory $optionFactory,
        BaseHelper $baseHelper,
        OptionDataCollector $optionDataCollector,
        ManagerInterface $eventManager,
        ResourceConnection $resource,
        DataSaver $dataSaver,
        GroupModel $groupModel,
        ProductCollectionFactory $productCollectionFactory,
        MageworxOptionResource $mageworxOptionResource,
        CollectionUpdaterRegistry $collectionUpdaterRegistry,
        ValidationResolver $validationResolver
    ) {
        $this->optionFactory             = $optionFactory;
        $this->baseHelper                = $baseHelper;
        $this->optionDataCollector       = $optionDataCollector;
        $this->dataSaver                 = $dataSaver;
        $this->eventManager              = $eventManager;
        $this->resource                  = $resource;
        $this->groupModel                = $groupModel;
        $this->mageworxOptionResource    = $mageworxOptionResource;
        $this->productCollectionFactory  = $productCollectionFactory;
        $this->collectionUpdaterRegistry = $collectionUpdaterRegistry;
        $this->validationResolver        = $validationResolver;
    }

    /**
     * Add product options
     *
     * @param array $data
     * @param array $productAttributesData
     * @param array $productSkuToGroupIdRelations
     * @param string $migrationMode
     * @throws LocalizedException
     */
    public function addProductOptions($data, $productAttributesData, $productSkuToGroupIdRelations, $migrationMode)
    {
        if ($migrationMode === MigrationMode::MIGRATION_MODE_DELETE_ALL_OPTIONS) {
            $this->mageworxOptionResource->removeCustomizableOptions(true);
        } elseif ($migrationMode === MigrationMode::MIGRATION_MODE_DELETE_OPTIONS_ON_INTERSECTING_PRODUCTS) {
            $this->mageworxOptionResource->removeCustomizableOptions(false, array_keys($data));
        }

        // TODO need recheck
        $this->linkField = $this->baseHelper->getLinkField();
        $allProductSkus  = array_map('strval', array_keys($data));

        $totalSkus = count($allProductSkus);
        $limit     = 50;

        for ($offset = 0; $offset < $totalSkus; $offset += $limit) {
            $skus       = array_slice($allProductSkus, $offset, $limit);
            $collection = $this->getProductsCollection($skus);

            if (empty($collection->getItems())) {
                continue;
            }

            $this->processIncrementIds();
            $this->processProducts($collection, $data, $productAttributesData, $productSkuToGroupIdRelations);
            $this->prepareProductStatusToUpdate(
                $this->productsHasNoRequiredOptions,
                $this->productsWithRequiredOptions,
                $this->productsWithMageWorxIsRequire,
                $this->productsHasNoOptions
            );
        }
    }

    /**
     * Process product changes and collect default magento data from options
     *
     * @param ProductCollection $collection
     * @param array $importData
     * @param array $productAttributesData
     * @param array $productSkuToGroupIdRelations
     */
    protected function processProducts($collection, $importData, $productAttributesData, $productSkuToGroupIdRelations)
    {
        $this->optionData              = [];
        $this->optionsToDelete         = [];
        $products                      = [];
        $productIds                    = [];
        $preparedProductAttributesData = [];
        $productIdsToDelete            = [];

        foreach ($collection as $product) {
            $this->addOptions($product, $importData[$product->getSku()]);
            $products[]   = $product;
            $productIds[] = $product->getId();
            $this->prepareProductAttributes(
                $preparedProductAttributesData,
                $productIdsToDelete,
                $product,
                $productAttributesData
            );
        }
        $this->saveOptions($products, $productSkuToGroupIdRelations);
        $this->saveProductAttributes($preparedProductAttributesData, $productIdsToDelete);

        //process adding initial states and dependency rules for imported product options
        $this->eventManager->dispatch(
            'mageworx_optionbase_product_mageone_import_after',
            ['product_ids' => $productIds]
        );
    }

    /**
     * Collect data form options for multiple insert
     *
     * @param ProductInterface $product
     * @param array $options
     * @return void
     */
    protected function addOptions(&$product, $options)
    {
        $this->preparedOptions  = [];
        $this->sortOrderCounter = 1;

        $this->processOriginalOptions($product);
        $this->processImportedOptions($product, $options);

        $product->setOptions($this->preparedOptions);
        $product->setCanSaveCustomOptions(true);

        $this->optionDataCollector->collectOptionsBeforeInsert(
            $product,
            $this->optionData,
            $this->optionsToDelete
        );
    }

    /**
     * Prepare data form product attributes for multiple insert
     *
     * @param array $preparedProductAttributesData
     * @param array $productIdsToDelete
     * @param ProductInterface $product
     * @param array $productAttributesData
     * @return void
     */
    protected function prepareProductAttributes(
        &$preparedProductAttributesData,
        &$productIdsToDelete,
        $product,
        $productAttributesData
    ) {
        if (empty($productAttributesData[$product->getSku()])) {
            return;
        }
        $productIdsToDelete[]            = $product->getData($this->linkField);
        $preparedProductAttributesData[] = array_merge(
            $productAttributesData[$product->getSku()],
            ['product_id' => $product->getData($this->linkField)]
        );
    }

    /**
     * Save product attributes
     *
     * @param array $preparedProductAttributesData
     * @param array $productIdsToDelete
     * @return void
     */
    protected function saveProductAttributes($preparedProductAttributesData, $productIdsToDelete)
    {
        if (!$preparedProductAttributesData) {
            return;
        }

        $connection = $this->resource->getConnection();
        $tableName  = $this->resource->getTableName(ProductAttributes::TABLE_NAME);

        $connection->delete(
            $tableName,
            ['product_id IN (?)' => array_values($productIdsToDelete)]
        );
        $connection->insertMultiple(
            $tableName,
            $preparedProductAttributesData
        );
    }

    /**
     * Process original product options
     *
     * @param ProductInterface $product
     * @return void
     */
    protected function processOriginalOptions($product)
    {
        $customOptions = $product->getOptions();
        if (!$customOptions || !is_array($customOptions)) {
            return;
        }

        $orderedOriginalOptions = [];
        foreach ($customOptions as $customOption) {
            $sortOrder = $customOption->getData('sort_order');
            while (isset($orderedOriginalOptions[$sortOrder])) {
                $sortOrder++;
            }
            $orderedOriginalOptions[$sortOrder] = $customOption;
        }

        foreach ($orderedOriginalOptions as $customOption) {
            $valueObjects = $customOption->getValues();
            if ($valueObjects && is_array($valueObjects)) {
                $values = [];
                foreach ($valueObjects as $valueObject) {
                    $values[] = $valueObject->getData();
                }
                $customOption->setData('values', $values);
            }
            $customOption->setData('sort_order', $this->sortOrderCounter);
            $this->sortOrderCounter++;
            $this->preparedOptions[] = $customOption;
        }
    }

    /**
     * Process imported options
     *
     * @param ProductInterface $product
     * @param array $options
     * @return void
     */
    protected function processImportedOptions($product, $options)
    {
        if (!$options || !is_array($options)) {
            return;
        }

        $orderedImportedOptions = [];
        foreach ($options as $option) {
            if (!isset($option['sort_order'])) {
                $option['sort_order'] = 1;
            }
            $sortOrder = $option['sort_order'];
            while (isset($orderedImportedOptions[$sortOrder])) {
                $sortOrder++;
            }
            $orderedImportedOptions[$sortOrder] = $option;
        }

        foreach ($orderedImportedOptions as $option) {
            $option['id']                         = $this->currentIncrementIds['option'];
            $option['option_id']                  = $option['id'];
            $option['need_to_process_dependency'] = true;
            if (empty($option['group_option_id'])) {
                $option['group_option_id'] = null;
            }
            ++$this->currentIncrementIds['option'];

            $this->addIncrementIdsToValues($option);

            $customOption = $this->optionFactory->create(['data' => $option]);
            $values       = !empty($option['values']) ? $option['values'] : [];
            $customOption->setProductSku($product->getSku())
                         ->setData('sort_order', $this->sortOrderCounter)
                         ->setValues($values);
            $this->sortOrderCounter++;
            $this->preparedOptions[] = $customOption;
        }
    }

    /**
     * Add increment ids to values
     *
     * @param array $option
     * @return void
     */
    protected function addIncrementIdsToValues(&$option)
    {
        if (!empty($option['values'])) {
            foreach ($option['values'] as $valueKey => $value) {
                if (empty($value['group_option_value_id'])) {
                    $value['group_option_value_id'] = null;
                }

                $value['id']             = $this->currentIncrementIds['value'];
                $value['option_type_id'] = $value['id'];
                ++$this->currentIncrementIds['value'];

                $value['need_to_process_dependency'] = true;
                $option['values'][$valueKey]         = $value;
            }
        }
    }

    /**
     * Try to collect current increment IDs for option and values and throw error if something wrong
     *
     * @return void
     * @throws LocalizedException
     */
    protected function processIncrementIds()
    {
        $this->collectCurrentIncrementIds();
        if (empty($this->currentIncrementIds['option']) || empty($this->currentIncrementIds['value'])) {
            throw new LocalizedException(__("Can't get current auto_increment ID"));
        }
    }

    /**
     * Collect current increment IDs for option and values
     *
     * @return void
     */
    protected function collectCurrentIncrementIds()
    {
        $this->currentIncrementIds = [];

        $optionTableStatus = $this->resource->getConnection()->showTableStatus(
            $this->resource->getTableName('catalog_product_option')
        );
        if (!empty($optionTableStatus['Auto_increment'])) {
            $this->currentIncrementIds['option'] = $optionTableStatus['Auto_increment'];
        }

        $valueTableStatus = $this->resource->getConnection()->showTableStatus(
            $this->resource->getTableName('catalog_product_option_type_value')
        );
        if (!empty($valueTableStatus['Auto_increment'])) {
            $this->currentIncrementIds['value'] = $valueTableStatus['Auto_increment'];
        }
    }

    /**
     * Get product collection using selected product SKUs
     *
     * @param array $skus
     * @return ProductCollection $collection
     */
    protected function getProductsCollection($skus)
    {
        $collection = $this->productCollectionFactory->create();
        $this->collectionUpdaterRegistry->setCurrentEntityType('product');
        $this->collectionUpdaterRegistry->setCurrentEntityIds([]);
        $this->collectionUpdaterRegistry->setOptionIds([]);
        $this->collectionUpdaterRegistry->setOptionValueIds([]);
        $this->baseHelper->resetOptionIdsCache();
        $collection->addStoreFilter(0)
                   ->setStoreId(0)
                   ->addFieldToFilter('sku', ['in' => $skus])
                   ->addOptionsToResult();

        return $collection;
    }

    /**
     * Save options by multiple insert and update needed products
     *
     * @param array $products
     * @param array $productSkuToGroupIdRelations
     * @return void
     */
    protected function saveOptions($products, $productSkuToGroupIdRelations)
    {
        if ($this->optionsToDelete) {
            $condition = 'option_id IN (' . implode(',', $this->optionsToDelete) . ')';
            $this->dataSaver->deleteData('catalog_product_option', $condition);
            $condition = 'option_id IN (' . implode(',', $this->optionsToDelete) . ')';
            $this->dataSaver->deleteData('catalog_product_option_type_value', $condition);
        }

        foreach ($this->optionData as $tableName => $dataItem) {
            $this->dataSaver->insertMultipleData($tableName, $dataItem);
        }

        $this->productsHasNoRequiredOptions  = [];
        $this->productsWithRequiredOptions   = [];
        $this->productsWithMageWorxIsRequire = [];
        $this->productsHasNoOptions          = [];

        foreach ($products as $productItem) {
            $this->updateProductData($productItem);
            if (!isset($productSkuToGroupIdRelations[$productItem->getSku()])
                || !is_array($productSkuToGroupIdRelations[$productItem->getSku()])
            ) {
                continue;
            }
            foreach (array_values($productSkuToGroupIdRelations[$productItem->getSku()]) as $groupId) {
                $this->groupModel->addRelation($groupId, $productItem->getId());
            }
        }
    }

    /**
     * Transfer product based custom options attributes to the corresponding product
     *
     */
    public function updateProductData(Product $product): void
    {

        $options                     = $product->getOptions();
        $hasOptions                  = false;
        $optionRequireStatus         = false;
        $mageWorxOptionRequireStatus = false;
        if ($options && is_array($options)) {
            $productId = $product->getId() ?? $product->getData($this->linkField);
            foreach ($options as $option) {
                if (!$option->getData('is_delete')) {
                    $hasOptions = true;

                    if ($option->getIsRequire()) {
                        $optionRequireStatus = true;
                    }
                    if ($optionRequireStatus) {
                        $mageWorxOptionRequireStatus = true;
                        /* @var ValidatorInterface $validatorItem */
                        foreach ($this->validationResolver->getValidators() as $key => $validatorItem) {
                            if (!$validatorItem->canValidateCartCheckout($product, $option)) {
                                $mageWorxOptionRequireStatus = false;
                                break;
                            }
                        }
                        if ($mageWorxOptionRequireStatus) {
                            break;
                        }
                    }
                }
            }

            if (!$hasOptions) {
                $this->productsHasNoOptions[] = $productId;
            } elseif (!$optionRequireStatus) {
                $this->productsHasNoRequiredOptions[] = $productId;
            } elseif (!$mageWorxOptionRequireStatus) {
                $this->productsWithRequiredOptions[] = $productId;
            } else {
                $this->productsWithMageWorxIsRequire[] = $productId;
            }
            $product->setHasOptions($hasOptions);
            $product->setRequiredOptions($optionRequireStatus);
            $product->setMageworxIsRequired($mageWorxOptionRequireStatus);
        }

        $product->setIsAfterTemplateSave(true);

        $this->eventManager->dispatch(
            'mageworx_attributes_save_trigger',
            ['product' => $product, 'is_after_template' => false]
        );
    }

    /**
     * Prepare has_options, required_options, mageworx_is_require flags for update
     *
     * @param $productsHasNoRequiredOptionsIds
     * @param $productWithRequireOptionIds
     * @param $productWithMageWorxIsRequireIds
     * @param $productHasNoOptionIds
     */
    public function prepareProductStatusToUpdate(
        $productsHasNoRequiredOptionsIds,
        $productWithRequireOptionIds,
        $productWithMageWorxIsRequireIds,
        $productHasNoOptionIds
    ) {

        $hasOption                  = 'has_options';
        $hasRequiredOptions         = 'required_options';
        $hasMageWorxRequiredOptions = 'mageworx_is_require';

        if ($productHasNoOptionIds) {
            $data = [
                $hasOption                  => false,
                $hasRequiredOptions         => false,
                $hasMageWorxRequiredOptions => false
            ];
            $this->mageworxOptionResource->updateProductStatusAttributes($data, $productHasNoOptionIds);
        }

        if ($productsHasNoRequiredOptionsIds) {
            $data = [
                $hasOption                  => true,
                $hasRequiredOptions         => false,
                $hasMageWorxRequiredOptions => false
            ];
            $this->mageworxOptionResource->updateProductStatusAttributes($data, $productsHasNoRequiredOptionsIds);
        }

        if ($productWithRequireOptionIds) {
            $data = [
                $hasOption                  => true,
                $hasRequiredOptions         => true,
                $hasMageWorxRequiredOptions => false
            ];
            $this->mageworxOptionResource->updateProductStatusAttributes($data, $productWithRequireOptionIds);
        }

        if ($productWithMageWorxIsRequireIds) {
            $data = [
                $hasOption                  => true,
                $hasRequiredOptions         => true,
                $hasMageWorxRequiredOptions => true
            ];
            $this->mageworxOptionResource->updateProductStatusAttributes($data, $productWithMageWorxIsRequireIds);
        }
    }

    /**
     * Check if products exist in magento
     *
     * @param array $data
     * @return bool
     */
    public function isProductsExist($data)
    {
        return $this->mageworxOptionResource->isProductsExist(array_keys($data));
    }

    /**
     * Check if custom options exist in magento
     *
     * @return bool
     */
    public function isCustomOptionsExist()
    {
        return $this->mageworxOptionResource->isCustomOptionsExist();
    }

    /**
     * Check if custom options in magento intersects with data
     *
     * @param array $data
     * @return bool
     */
    public function hasIntersectingProducts($data)
    {
        return $this->mageworxOptionResource->hasIntersectingProducts(array_keys($data));
    }
}
