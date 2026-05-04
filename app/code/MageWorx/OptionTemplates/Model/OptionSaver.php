<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionTemplates\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Webapi\Exception;
use Magento\ConfigurableProduct\Model\Product\ReadHandler;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductCustomOptionRepositoryInterface as OptionRepository;
use MageWorx\OptionBase\Model\ValidationResolver as ValidationResolver;
use MageWorx\OptionTemplates\Helper\Data as Helper;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\App\ResourceConnection;
use MageWorx\OptionBase\Model\ResourceModel\CollectionUpdaterRegistry;
use MageWorx\OptionTemplates\Model\ResourceModel\Product as ResourceModelProduct;
use MageWorx\OptionBase\Model\AttributeSaver;
use MageWorx\OptionBase\Model\ResourceModel\DataSaver;
use MageWorx\OptionBase\Model\ResourceModel\Option as MageworxOptionResource;
use MageWorx\OptionBase\Model\OptionSaver\Option as OptionDataCollector;
use Magento\Store\Model\StoreManagerInterface as StoreManager;
use MageWorx\OptionBase\Model\OptionHandler as OptionHandler;

class OptionSaver
{
    const SAVE_MODE_ADD_DELETE = 'add_delete';
    const SAVE_MODE_UPDATE     = 'update';

    const KEY_NEW_PRODUCT = 'new';
    const KEY_UPD_PRODUCT = 'upd';
    const KEY_DEL_PRODUCT = 'del';

    /**
     * @var StoreManager
     */
    protected $storeManager;

    /**
     * @var ManagerInterface
     */
    protected $eventManager;
    protected \MageWorx\OptionBase\Model\Entity\Product $productEntity;
    protected \MageWorx\OptionBase\Model\Entity\Group $groupEntity;

    /**
     * @var ReadHandler
     */
    private $readHandler;

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * @var BaseHelper
     */
    protected $baseHelper;

    /**
     * @var \Magento\Catalog\Model\ProductOptions\ConfigInterface
     */
    protected $productOptionConfig;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var \MageWorx\OptionTemplates\Model\GroupFactory
     */
    protected $groupFactory;

    /**
     * Array contain all group option ids, that were added to personal product
     *
     * @var array
     */
    protected $productGroupNewOptionIds = [];

    /**
     * @var \MageWorx\OptionTemplates\Model\Group
     */
    protected $group;

    /**
     *
     * @var array
     */
    protected $deletedGroupOptions;

    /**
     *
     * @var array
     */
    protected $addedGroupOptions;

    /**
     *
     * @var array
     */
    protected $intersectedOptions;

    /**
     *
     * @var array
     */
    protected $products = [];

    /**
     * Array of modified options
     *
     * @var array
     */
    protected $modifiedGroupOptions;

    /**
     * Added product option values to template options
     * NEED to be deleted after template re-applying
     *
     * @var array
     */
    protected $addedProductValues;

    /**
     * @var \Magento\Catalog\Api\Data\ProductCustomOptionInterfaceFactory
     */
    protected $customOptionFactory;

    /**
     * @var OptionRepository
     */
    protected $optionRepository;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var array|null
     */
    protected $groupOptions;

    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $productRepository;

    /**
     * @var \MageWorx\OptionTemplates\Model\Group\Source\SystemAttributes
     */
    protected $systemAttributes;

    /**
     * @var array|null
     */
    protected $oldGroupCustomOptions;

    /**
     * @var array
     */
    protected $oldGroupCustomOptionValues;

    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var CollectionUpdaterRegistry
     */
    protected $collectionUpdaterRegistry;

    /**
     * @var ResourceModelProduct
     */
    protected $resourceModelProduct;

    /**
     * @var AttributeSaver
     */
    protected $attributeSaver;

    /**
     * @var OptionDataCollector
     */
    protected $optionDataCollector;

    /**
     * @var MageworxOptionResource
     */
    protected $mageworxOptionResource;

    /**
     * @var DataSaver
     */
    protected $dataSaver;

    /**
     * @var array
     */
    protected $currentIncrementIds = [];

    /**
     * @var array
     */
    protected $optionData = [];

    /**
     * @var array
     */
    protected $optionsToDelete = [];

    /**
     * @var array
     */
    protected $productsHasNoRequiredOptions = [];

    /**
     * @var array
     */
    protected $productsWithRequiredOptions = [];

    /**
     * @var array
     */
    protected $linkField = [];

    /**
     * @var bool
     */
    protected $isTemplateSave = true;

    /**
     * @var array
     */
    protected $productsHasNoOptions = [];

    /**
     * @var array
     */
    protected $productsWithMageWorxIsRequire = [];

    /**
     * @var array
     */
    protected $optionHandler = [];

    /**
     * @var ValidationResolver
     */
    protected $validationResolver;

    /**
     *
     * @param \Magento\Catalog\Model\ProductOptions\ConfigInterface $productOptionConfig
     * @param \MageWorx\OptionTemplates\Model\GroupFactory $groupFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\Catalog\Api\Data\ProductCustomOptionInterfaceFactory $customOptionFactory
     * @param OptionRepository $optionRepository
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param \Psr\Log\LoggerInterface $logger
     * @param ManagerInterface $eventManager
     * @param ResourceConnection $resource
     * @param CollectionUpdaterRegistry $collectionUpdaterRegistry
     * @param ResourceModelProduct $resourceModelProduct
     * @param OptionDataCollector $optionDataCollector
     * @param AttributeSaver $attributeSaver
     * @param DataSaver $dataSaver
     * @param MageworxOptionResource $mageworxOptionResource
     * @param StoreManager $storeManager
     * @param ValidationResolver $validationResolver
     * @param OptionHandler $optionHandler
     */
    public function __construct(
        ReadHandler $readHandler,
        \Magento\Catalog\Model\ProductOptions\ConfigInterface $productOptionConfig,
        \MageWorx\OptionTemplates\Model\GroupFactory $groupFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Api\Data\ProductCustomOptionInterfaceFactory $customOptionFactory,
        OptionRepository $optionRepository,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Psr\Log\LoggerInterface $logger,
        Helper $helper,
        BaseHelper $baseHelper,
        \MageWorx\OptionTemplates\Model\Group\Source\SystemAttributes $systemAttributes,
        \MageWorx\OptionBase\Model\Entity\Group $groupEntity,
        \MageWorx\OptionBase\Model\Entity\Product $productEntity,
        ManagerInterface $eventManager,
        CollectionUpdaterRegistry $collectionUpdaterRegistry,
        ResourceConnection $resource,
        ResourceModelProduct $resourceModelProduct,
        OptionDataCollector $optionDataCollector,
        AttributeSaver $attributeSaver,
        MageworxOptionResource $mageworxOptionResource,
        DataSaver $dataSaver,
        StoreManager $storeManager,
        OptionHandler $optionHandler,
        ValidationResolver $validationResolver
    ) {
        $this->readHandler               = $readHandler;
        $this->productOptionConfig       = $productOptionConfig;
        $this->groupFactory              = $groupFactory;
        $this->productCollectionFactory  = $productCollectionFactory;
        $this->customOptionFactory       = $customOptionFactory;
        $this->optionRepository          = $optionRepository;
        $this->productRepository         = $productRepository;
        $this->helper                    = $helper;
        $this->baseHelper                = $baseHelper;
        $this->logger                    = $logger;
        $this->systemAttributes          = $systemAttributes;
        $this->groupEntity               = $groupEntity;
        $this->productEntity             = $productEntity;
        $this->eventManager              = $eventManager;
        $this->collectionUpdaterRegistry = $collectionUpdaterRegistry;
        $this->resource                  = $resource;
        $this->resourceModelProduct      = $resourceModelProduct;
        $this->optionDataCollector       = $optionDataCollector;
        $this->attributeSaver            = $attributeSaver;
        $this->dataSaver                 = $dataSaver;
        $this->mageworxOptionResource    = $mageworxOptionResource;
        $this->storeManager              = $storeManager;
        $this->optionHandler             = $optionHandler;
        $this->validationResolver        = $validationResolver;
    }

    /**
     * Modify product options using template options
     * Save mode 'add_delete': add template options to new products, delete template options from unassigned products
     * Save mode 'update': similar to 'add_delete' + rewrite template options on existing products
     *
     * @param Group $group
     * @param array $oldGroupCustomOptions
     * @param string $saveMode
     * @return void
     */
    public function saveProductOptions(Group $group, $oldGroupCustomOptions, $saveMode)
    {
        $this->baseHelper->resetOptionIdsCache();

        $this->products[self::KEY_NEW_PRODUCT] = $group->getNewProductIds();
        $this->products[self::KEY_UPD_PRODUCT] = $group->getUpdProductIds();
        $this->products[self::KEY_DEL_PRODUCT] = $group->getDelProductIds();
        $allProductIds                         = $group->getAffectedProductIds();

        $totalIds = count($allProductIds);
        $limit    = 50;

        for ($offset = 0; $offset < $totalIds; $offset += $limit) {
            $ids        = array_slice($allProductIds, $offset, $limit);
            $collection = $this->getAffectedProductsCollection($ids);

            if (empty($collection->getItems())) {
                continue;
            }

            if ($saveMode == static::SAVE_MODE_UPDATE) {
                /** Reload model for using new option ids **/
                $this->checkPercentPriceOnConfigurable($group);
                /** @var Group group */
                $this->group = $this->groupFactory->create()->load($group->getId());
                $this->grabOptionsDiff($oldGroupCustomOptions);
            } else {
                $this->group = $group;
            }

            $this->processIncrementIds();
            $this->processProducts($collection, $saveMode);
            $this->optionHandler->prepareProductStatusToUpdate(
                $this->productsHasNoRequiredOptions,
                $this->productsWithRequiredOptions,
                $this->productsWithMageWorxIsRequire,
                $this->productsHasNoOptions
            );
        }

        $this->eventManager->dispatch('mw_optiontemplates_product_options_save_after', ['group' => $group]);

        return;
    }

    /**
     * Save options by multiple insert
     *
     * @param array $products
     * @return void
     */
    protected function saveOptions($products)
    {
        $this->resource->getConnection()->beginTransaction();
        try {
            if ($this->optionsToDelete) {
                $condition = 'option_id IN (' . implode(',', $this->optionsToDelete) . ')';
                $this->dataSaver->deleteData('catalog_product_option', $condition);
                $condition = 'option_id IN (' . implode(',', $this->optionsToDelete) . ')';
                $this->dataSaver->deleteData('catalog_product_option_type_value', $condition);
            }

            //saving custom options to products
            foreach ($this->optionData as $tableName => $dataItem) {
                $this->dataSaver->insertMultipleData($tableName, $dataItem);
            }

            $this->linkField                     = $this->baseHelper->getLinkField(ProductInterface::class);
            $this->productsHasNoRequiredOptions  = [];
            $this->productsWithRequiredOptions   = [];
            $this->productsWithMageWorxIsRequire = [];
            $this->productsHasNoOptions          = [];

            foreach ($products as $productItem) {
                $this->updateProductData($productItem);
                $this->doProductRelationAction($productItem->getId());
            }

            //saving APO attributes to products
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
            $this->logger->critical($e->getMessage());
            $this->resource->getConnection()->rollBack();
            throw $e;
        }
        $this->attributeSaver->clearAttributeData();
    }

    /**
     * Get product collection using selected product IDs
     *
     * @param array $ids
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     */
    protected function getAffectedProductsCollection($ids)
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection */
        $collection = $this->productCollectionFactory->create();
        $this->collectionUpdaterRegistry->setCurrentEntityType('product');
        $this->collectionUpdaterRegistry->setCurrentEntityIds([]);
        $this->collectionUpdaterRegistry->setOptionIds([]);
        $this->collectionUpdaterRegistry->setOptionValueIds([]);
        $this->baseHelper->resetOptionIdsCache();
        $this->storeManager->setCurrentStore(0);
        $collection->addStoreFilter(0)
                   ->setStoreId(0)
                   ->addFieldToFilter('entity_id', ['in' => $ids])
                   ->addOptionsToResult();

        return $collection;
    }

    /**
     * Collect difference between old template options and the new one
     *
     * @param array $oldGroupCustomOptions
     * @return void
     */
    protected function grabOptionsDiff($oldGroupCustomOptions)
    {
        $this->groupOptions               = $this->groupEntity->getOptionsAsArray($this->group);
        $this->oldGroupCustomOptions      = $oldGroupCustomOptions;
        $this->oldGroupCustomOptionValues = $this->getOptionValues($this->oldGroupCustomOptions);
        $this->deletedGroupOptions        = $this->getGroupDeletedOptions();
        $this->addedGroupOptions          = $this->getGroupAddedOptions();
        $this->intersectedOptions         = $this->getGroupIntersectedOptions();
        $groupNewModifiedOptions          = $this->getGroupNewModifiedOptions();
        $groupLastModifiedOptions         = $this->getGroupLastModifiedOptions();

        $this->filterOptionsDiff($groupLastModifiedOptions);
        $this->filterOptionsDiff($groupNewModifiedOptions);

        $modifiedDownGroupOptions   = $this->arrayDiffRecursive(
            $groupLastModifiedOptions,
            $groupNewModifiedOptions
        );
        $modifiedUpGroupOptions     = $this->arrayDiffRecursive(
            $groupNewModifiedOptions,
            $groupLastModifiedOptions
        );
        $this->modifiedGroupOptions = $modifiedDownGroupOptions + $modifiedUpGroupOptions;
    }

    /**
     * Filter options diff from repeated data
     *
     * @param array $options
     * @return void
     */
    protected function filterOptionsDiff(&$options)
    {
        if (empty($options)) {
            return;
        }
        foreach ($options as $optionKey => $option) {
            if (!isset($option['values'])) {
                continue;
            }

            foreach ($option['values'] as $valueKey => $optionValue) {
                if (empty($optionValue['images_data'])) {
                    continue;
                }

                $modifiedImagesData = $this->baseHelper->jsonDecode($optionValue['images_data']);
                if (!$modifiedImagesData) {
                    continue;
                }

                foreach ($modifiedImagesData as $key => $imageData) {
                    unset($modifiedImagesData[$key]['option_type_image_id']);
                }
                $options[$optionKey]['values'][$valueKey]['images_data'] = $this->baseHelper->jsonEncode(
                    $modifiedImagesData
                );
            }
        }
    }

    /**
     * Try to collect current increment IDs for option and values and throw error if something wrong
     *
     * @return void
     */
    protected function processIncrementIds()
    {
        try {
            $this->collectCurrentIncrementIds();
            if (empty($this->currentIncrementIds['option']) || empty($this->currentIncrementIds['value'])) {
                throw new Exception(__("Can't get current auto_increment ID"));
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getLogMessage());
            throw $e;
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
     * Process template-to-product relation changes and collect default magento data from options
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @param string $saveMode
     * @throws \Exception
     */
    protected function processProducts($collection, $saveMode)
    {
        $this->optionData      = [];
        $this->optionsToDelete = [];
        $products              = [];

        /** @var \Magento\Catalog\Model\Product $product */
        foreach ($collection as $product) {
            $customOptions = [];
            $this->clearProductGroupNewOptionIds();
            $product->setStoreId(0);
            $preparedProductOptionArray = $this->getPreparedProductOptions($product, $saveMode);

            try {
                foreach ($preparedProductOptionArray as $preparedOption) {
                    /** @var \Magento\Catalog\Api\Data\ProductCustomOptionInterface $customOption */
                    if (is_object($preparedOption)) {
                        $customOption = $this->customOptionFactory->create(['data' => $preparedOption->getData()]);
                        $id           = $preparedOption->getData('id');
                        $values       = $preparedOption->getValues();
                    } elseif (is_array($preparedOption)) {
                        $customOption = $this->customOptionFactory->create(['data' => $preparedOption]);
                        $id           = $preparedOption['id'];
                        $values       = !empty($preparedOption['values']) ? $preparedOption['values'] : [];
                    } else {
                        throw new Exception(
                            __(
                                'The prepared option is not an instance of DataObject or array. %1 is received',
                                gettype($preparedOption)
                            )
                        );
                    }

                    $customOption->setProductSku($product->getSku())
                                 ->setOptionId($id)
                                 ->setValues($values);
                    $customOptions[] = $customOption;
                }
                if (!empty($customOptions)) {
                    $product->setOptions($customOptions);
                    $product->setCanSaveCustomOptions(true);

                    $this->optionDataCollector->collectOptionsBeforeInsert(
                        $product,
                        $this->optionData,
                        $this->optionsToDelete
                    );
                }
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->logger->critical($e->getLogMessage());
                throw $e;
            } catch (\Exception $e) {
                $this->logger->critical($e->getMessage());
                throw $e;
            }
            $products[] = $product;
        }
        $this->saveOptions($products);
    }

    /**
     * Get values from options
     *
     * @param array|null $options
     * @return array $values
     */
    protected function getOptionValues($options)
    {
        $values = [];
        if (empty($options)) {
            return $values;
        }

        foreach ($options as $option) {
            if (empty($option['values'])) {
                continue;
            }
            foreach ($option['values'] as $valueKey => $value) {
                $values[$valueKey] = $value;
            }
        }

        return $values;
    }


    /**
     * Transfer product based custom options attributes from group to the corresponding product
     *
     * @param \Magento\Catalog\Model\Product $product
     */
    protected function updateProductData($product)
    {
        $excludeAttributes = $this->systemAttributes->toArray();
        $groupData         = $this->group->getData();
        foreach ($excludeAttributes as $attribute) {
            unset($groupData[$attribute]);
        }

        if ($product->getTypeId() == Configurable::TYPE_CODE) {
            $this->readHandler->execute($product);
        }

        $product->addData($groupData);
        $options                     = $product->getOptions();
        $hasOptions                  = false;
        $optionRequireStatus         = false;
        $mageWorxOptionRequireStatus = false;
        if ($options && is_array($options)) {
            $productId = $product->getData($this->linkField);
            foreach ($options as $option) {
                if (!$option->getData('is_delete')) {
                    $hasOptions = true;

                    if ($option->getIsRequire()) {
                        $optionRequireStatus = true;
                    }
                    if ($optionRequireStatus) {
                        $mageWorxOptionRequireStatus = true;
                        /* @var $validatorItem \MageWorx\OptionBase\Api\ValidatorInterface */
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
            } elseif ($optionRequireStatus && !$mageWorxOptionRequireStatus) {
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
            ['product' => $product, 'is_after_template' => true]
        );
    }

    /**
     * Check percentage options restriction on configurable products
     *
     * @param Group $group
     * @throws LocalizedException
     */
    protected function checkPercentPriceOnConfigurable($group)
    {
        if (empty($group->getData('options'))) {
            return;
        }
        $isPercentTypeExist = false;
        foreach ($group->getData('options') as $option) {
            if (isset($option['price_type']) && $option['price_type'] == 'percent') {
                $isPercentTypeExist = true;
                break;
            }
            if (empty($option['values'])) {
                continue;
            }
            foreach ($option['values'] as $value) {
                if (isset($value['price_type']) && $value['price_type'] == 'percent') {
                    $isPercentTypeExist = true;
                    break;
                }
            }
        }
        if (!$isPercentTypeExist) {
            return;
        }

        $newAndUpdatedProductIds = [];
        foreach ($this->products[self::KEY_NEW_PRODUCT] as $productId) {
            $newAndUpdatedProductIds[] = $productId;
        }
        foreach ($this->products[self::KEY_UPD_PRODUCT] as $productId) {
            $newAndUpdatedProductIds[] = $productId;
        }
        if ($newAndUpdatedProductIds) {
            $newAndUpdatedProducts = $this->resourceModelProduct->getProductsByIds($newAndUpdatedProductIds);
            foreach ($newAndUpdatedProducts as $newAndUpdatedProduct) {
                if (isset($newAndUpdatedProduct['type_id'])
                    && $newAndUpdatedProduct['type_id'] == Configurable::TYPE_CODE
                ) {
                    $message = 'Custom options with percentage price type ' .
                        'could not be saved on assigned configurable products, ' .
                        'because Magento 2 does not allow saving percentage options on configurable items. ' .
                        'Please, do not assign configurable products to template ' .
                        'or change price types from "percent" to "fixed"';
                    throw new LocalizedException(__($message));
                }
            }
        }
    }

    /**
     * @return void
     */
    protected function clearProductGroupNewOptionIds()
    {
        $this->productGroupNewOptionIds = [];
    }

    /**
     *
     * @return array
     */
    protected function getGroupDeletedOptions()
    {
        return array_diff_key($this->oldGroupCustomOptions, $this->groupOptions);
    }

    /**
     *
     * @return array
     */
    protected function getGroupAddedOptions()
    {
        return array_diff_key($this->groupOptions, $this->oldGroupCustomOptions);
    }

    /**
     *
     * @return array
     */
    protected function getGroupIntersectedOptions()
    {
        return array_intersect_key($this->groupOptions, $this->oldGroupCustomOptions);
    }

    /**
     *
     * @return array
     */
    protected function getGroupNewModifiedOptions()
    {
        $intersectedGroupOptionIds = array_keys($this->getGroupIntersectedOptions($this->oldGroupCustomOptions));
        $prepareNewGroupOptions    = [];

        foreach ($intersectedGroupOptionIds as $optionId) {
            if (!empty($this->groupOptions[$optionId])) {
                $prepareNewGroupOptions[$optionId] = $this->groupOptions[$optionId];
            }
        }

        return $prepareNewGroupOptions;
    }

    /**
     *
     * @return array
     */
    protected function getGroupLastModifiedOptions()
    {
        $intersectedGroupOptionIds = array_keys($this->getGroupIntersectedOptions($this->oldGroupCustomOptions));
        $prepareLastGroupOptions   = [];

        foreach ($intersectedGroupOptionIds as $optionId) {
            if (!empty($this->oldGroupCustomOptions[$optionId])) {
                $prepareLastGroupOptions[$optionId] = $this->oldGroupCustomOptions[$optionId];
            }
        }

        return $prepareLastGroupOptions;
    }

    /**
     * Retrieve new product options as array, that were built by group modification
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param string $saveMode
     * @return array
     */
    protected function getPreparedProductOptions($product, $saveMode)
    {
        $productOptions = $this->productEntity->getOptionsAsArray($product);
        $productSku     = $product->getSku();

        if ($saveMode == static::SAVE_MODE_UPDATE) {
            if ($this->isNewProduct($product->getId())) {
                $productOptions = $this->addNewOptionProcess($productOptions, $productSku);
            } elseif ($this->isUpdProduct($product->getId())) {
                $productOptions = $this->deleteOptionProcess($productOptions, $this->group, true);
                $productOptions = $this->addNewOptionProcess($productOptions, $productSku);
                $productOptions = $this->modifyOptionProcess($productOptions, $productSku);
            } elseif ($this->isDelProduct($product->getId())) {
                $productOptions = $this->deleteOptionProcess($productOptions, $this->group);
            }
        } else {
            if ($this->isNewProduct($product->getId())) {
                $productOptions = $this->addNewOptionProcess($productOptions, $productSku, $this->group);
            } elseif ($this->isDelProduct($product->getId())) {
                $productOptions = $this->deleteOptionProcess($productOptions, $this->group);
            }
        }

        return $productOptions;
    }

    /**
     * Delete options that were deleted in group
     *
     * @param array $productOptions
     * @param \MageWorx\OptionTemplates\Model\Group $group
     * @param boolean $fromModify
     * @return array
     */
    public function deleteOptionProcess(array $productOptions, $group, $fromModify = false)
    {
        if ($fromModify) {
            $deletedGroupOptionIds = array_keys($this->deletedGroupOptions);
        } else {
            $groupOptions          = $this->groupEntity->getOptionsAsArray($group);
            $deletedGroupOptionIds = array_keys($groupOptions);
        }

        foreach ($productOptions as $optionIndex => $productOption) {
            if ($this->isOptionDeletedFromGroup($productOption, $deletedGroupOptionIds)) {
                $productOption['is_delete']   = '1';
                $productOptions[$optionIndex] = $productOption;
            }
        }
        /* temporary fix, see APO issues 1076 */
        $this->deleteOptionDependencyData($productOptions);

        return $productOptions;
    }

    /**
     * Delete dependency data for deleted temaplte options
     *
     */
    public function deleteOptionDependencyData(array $productOptions): void
    {
        $data     = [];
        $optionId = 'option_id';
        foreach ($productOptions as $productOption) {
            if (isset($productOption['is_delete'])) {
                $data['delete'][] = [$optionId => $productOption[$optionId]];
            }
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

    /**
     * Check if options is deleted from group
     *
     * @param array $productOption
     * @param array $deletedGroupOptionIds
     * @return bool
     */
    protected function isOptionDeletedFromGroup($productOption, $deletedGroupOptionIds)
    {
        return !empty($productOption['group_option_id']) &&
            in_array($productOption['group_option_id'], $deletedGroupOptionIds);
    }

    /**
     * Delete all group options
     *
     * @param array $productOptions
     * @return array
     */
    protected function clearOptionProcess(array $productOptions)
    {
        foreach ($productOptions as $key => $productOption) {
            if (empty($productOption['group_option_id'])) {
                continue;
            }
            foreach ($this->group->getOptions() as $option) {
                if ($productOption['group_option_id'] == $option->getData('option_id')) {
                    $productOptions[$key]['is_delete'] = '1';
                }
            }
        }

        return $productOptions;
    }

    /**
     * Mark product option value to delete
     *
     * @param array $productOptionValue
     */
    protected function markToDeleteOptionValue(&$productOptionValue)
    {
        $productOptionValue['is_delete'] = 1;
    }

    /**
     * Modify options that were modified in group
     *
     * @param array $productOptions
     * @param $productSku
     * @return array
     */
    protected function modifyOptionProcess(array $productOptions, $productSku)
    {
        foreach ($productOptions as &$productOption) {
            $productOptionId = $productOption['option_id'];
            $groupOptionId   = $productOption['group_option_id'];
            if (!$groupOptionId) {
                continue;
            }

            if (empty($this->modifiedGroupOptions[$groupOptionId])) {
                continue;
            }

            $groupOption = $this->groupOptions[$groupOptionId];
            if (isset($productOption['values'])) {
                $productOptionValues = $productOption['values'];
            }

            $productOption                               = $groupOption;
            $productOption['group_option_id']            = $groupOptionId;
            $productOption['option_id']                  = $productOptionId;
            $productOption['id']                         = $productOptionId;
            $productOption['need_to_process_dependency'] = true;

            if (!$this->baseHelper->isSelectableOption($groupOption['type'])) {
                continue;
            }

            if (isset($productOptionValues)) {
                $productOption['values'] = $productOptionValues;
            }

            if ($this->baseHelper->isSelectableOption($groupOption['type'])
                && (empty($groupOption['values']) || !is_array($groupOption['values']))
            ) {
                continue;
            }

            $processedGroupValues = [];

            foreach ($productOption['values'] as &$productOptionValue) {
                $groupOptionValueId = $productOptionValue['group_option_value_id'];
                if ($groupOptionValueId) {
                    $processedGroupValues[$groupOptionValueId] = $groupOptionValueId;
                    if (isset($groupOption['values'][$groupOptionValueId])) {
                        $isDeafult = $this->baseHelper->setIsDefaultAttrForLLPLogic(
                            $productSku,
                            $groupOption['values'][$groupOptionValueId]
                        );

                        $productOptionValueId                             = $productOptionValue['option_type_id'];
                        $productOptionValue                               = $groupOption['values'][$groupOptionValueId];
                        $productOptionValue['group_option_value_id']      = $groupOptionValueId;
                        $productOptionValue['id']                         = $productOptionValueId;
                        $productOptionValue['option_type_id']             = $productOptionValueId;
                        $productOptionValue['option_id']                  = $productOptionId;
                        $productOptionValue['need_to_process_dependency'] = true;
                        $productOptionValue['is_default']                 = $isDeafult;
                    } else {
                        $this->markToDeleteOptionValue($productOptionValue);
                    }
                }
            }

            $additionalProductValues = [];
            foreach ($groupOption['values'] as $groupOptionValue) {
                if (isset($processedGroupValues[$groupOptionValue['option_type_id']])) {
                    continue;
                }

                $value                                             = $groupOptionValue;
                $value['group_option_value_id']                    = $value['option_type_id'];
                $value['id']                                       = $this->currentIncrementIds['value'];
                $value['option_type_id']                           = $value['id'];
                $value['option_id']                                = $productOptionId;
                $value['need_to_process_dependency']               = true;
                $this->currentIncrementIds['value']                += 1;
                $value['is_default']                               = $this->baseHelper->setIsDefaultAttrForLLPLogic(
                    $productSku,
                    $groupOptionValue
                );
                $additionalProductValues[$value['option_type_id']] = $value;
            }

            $productOption['values'] = array_merge($productOption['values'], $additionalProductValues);
        }

        return $productOptions;
    }

    /**
     * Add new options that were added in group
     *
     * @param array $productOptions
     * @param $productSku
     * @param null $group
     * @return array
     */
    public function addNewOptionProcess(array $productOptions, $productSku, $group = null)
    {
        if ($group === null) {
            $groupOptions = $this->groupOptions;
        } else {
            $groupOptions = $this->groupEntity->getOptionsAsArray($group);
        }

        foreach ($groupOptions as $groupOption) {
            $issetGroupOptionInProduct = false;
            foreach ($productOptions as $optionIndex => &$productOption) {
                if (empty($productOption['group_option_id'])
                    || $productOption['group_option_id'] !== $groupOption['option_id']
                ) {
                    continue;
                }

                $issetGroupOptionInProduct = true;
                if (isset($groupOption['dependency'])) {
                    $this->attributeSaver->addNewGroupOptionIds($groupOption['option_id']);
                    $productOption['dependency']                 = $groupOption['dependency'];
                    $productOption['need_to_process_dependency'] = true;
                }

                if (empty($productOption['values']) || !is_array($productOption['values'])
                    || empty($groupOption['values']) || !is_array($groupOption['values'])
                ) {
                    continue;
                }

                foreach ($productOption['values'] as &$productOptionValue) {
                    foreach ($groupOption['values'] as $groupOptionValue) {
                        if (!empty($groupOptionValue['load_linked_product'])) {
                            $productOptionValue['is_default'] = $this->baseHelper->setIsDefaultAttrForLLPLogic(
                                $productSku,
                                $productOptionValue
                            );
                        }
                        if (empty($productOptionValue['group_option_value_id'])
                            || $productOptionValue['group_option_value_id'] !== $groupOptionValue['option_type_id']
                            || !isset($groupOptionValue['dependency'])
                        ) {
                            continue;
                        }
                        $productOptionValue['dependency']                 = $groupOptionValue['dependency'];
                        $productOptionValue['need_to_process_dependency'] = true;

                        $this->attributeSaver->addNewGroupOptionIds($groupOption['option_id']);
                    }
                }
            }

            if (!$issetGroupOptionInProduct) {
                $groupOption['group_option_id'] = $groupOption['id'];
                if ($this->getIsTemplateSave()) {
                    $groupOption['id']        = $this->currentIncrementIds['option'];
                    $groupOption['option_id'] = $groupOption['id'];
                    ++$this->currentIncrementIds['option'];
                } else {
                    $groupOption['id']        = null;
                    $groupOption['option_id'] = null;
                }
                $this->attributeSaver->addNewGroupOptionIds($groupOption['group_option_id']);
                $groupOption['need_to_process_dependency'] = true;

                if (!empty($groupOption['values'])) {
                    foreach ($groupOption['values'] as &$groupValue) {
                        $groupValue['is_default'] = $this->baseHelper->setIsDefaultAttrForLLPLogic(
                            $productSku,
                            $groupValue
                        );
                    }
                }

                $groupOption                      = $this->convertGroupToProductOptionValues($groupOption);
                $productOptions[]                 = $groupOption;
                $this->productGroupNewOptionIds[] = $groupOption['group_option_id'];
            }
        }

        return $productOptions;
    }


    /**
     * Unassign options from template
     *
     * @param array $productOptions
     * @param Group $group
     * @return array
     */
    public function unassignOptions(array $productOptions, $group)
    {
        $groupOptions          = $this->groupEntity->getOptionsAsArray($group);
        $deletedGroupOptionIds = array_keys($groupOptions);

        foreach ($productOptions as $optionIndex => $productOption) {
            if (empty($productOption['group_option_id']) ||
                !in_array($productOption['group_option_id'], $deletedGroupOptionIds)
            ) {
                continue;
            }
            $productOptions[$optionIndex]['group_option_id'] = null;
            if (empty($productOption['values']) || !is_array($productOption['values'])) {
                continue;
            }
            foreach ($productOption['values'] as $valueIndex => $valueData) {
                $productOptions[$optionIndex]['values'][$valueIndex]['group_option_value_id'] = null;
            }
        }

        return $productOptions;
    }

    /**
     *
     * @param array $option
     * @return array
     */
    protected function convertGroupToProductOptionValues($option)
    {
        if (!empty($option['values'])) {
            foreach ($option['values'] as $valueKey => $value) {
                $value['group_option_value_id'] = $value['option_type_id'];
                if ($this->getIsTemplateSave()) {
                    $value['id']             = $this->currentIncrementIds['value'];
                    $value['option_type_id'] = $value['id'];
                    ++$this->currentIncrementIds['value'];
                } else {
                    $value['id']             = null;
                    $value['option_type_id'] = null;
                }
                $value['need_to_process_dependency'] = true;
                $option['values'][$valueKey]         = $value;
            }
        }

        return $option;
    }

    /**
     *
     * @param int $productId
     */
    protected function doProductRelationAction($productId)
    {
        if ($this->isNewProduct($productId)) {
            $this->group->addProductRelation($productId);
        } elseif ($this->isDelProduct($productId)) {
            $this->group->deleteProductRelation($productId);
        }
    }

    /**
     *
     * @param int $productId
     * @return boolean
     */
    protected function isNewProduct($productId)
    {
        return in_array($productId, $this->products[self::KEY_NEW_PRODUCT]);
    }

    /**
     *
     * @param int $productId
     * @return boolean
     */
    protected function isUpdProduct($productId)
    {
        return in_array($productId, $this->products[self::KEY_UPD_PRODUCT]);
    }

    /**
     *
     * @param int $productId
     * @return boolean
     */
    protected function isDelProduct($productId)
    {
        return in_array($productId, $this->products[self::KEY_DEL_PRODUCT]);
    }

    /**
     * Check if different options types
     *
     * @param string $typeOld
     * @param string $typeNew
     * @return bool
     */
    protected function isSameOptionGroupType($typeOld, $typeNew)
    {
        return ($this->getOptionGroupType($typeOld) == $this->getOptionGroupType($typeNew));
    }

    /**
     *
     * @param string $name
     * @return string
     */
    protected function getOptionGroupType($name)
    {
        foreach ($this->productOptionConfig->getAll() as $typeName => $data) {
            if (!empty($data['types'][$name])) {
                return $typeName;
            }
        }

        return null;
    }

    /**
     *
     * @param array $arr1
     * @param array $arr2
     * @return array
     */
    protected function arrayDiffRecursive($arr1, $arr2)
    {
        $outputDiff = [];

        foreach ($arr1 as $key => $value) {
            if (is_array($arr2) && array_key_exists($key, $arr2)) {
                if (is_array($value)) {
                    $recursiveDiff = $this->arrayDiffRecursive($value, $arr2[$key]);
                    if (count($recursiveDiff)) {
                        $outputDiff[$key] = $recursiveDiff;
                    }
                } elseif ($arr2[$key] != $value) {
                    $outputDiff[$key] = $value;
                }
            } else {
                $outputDiff[$key] = $value;
            }
        }

        return $outputDiff;
    }

    /**
     * Check if it is template saving or not
     *
     * @return bool
     */
    public function getIsTemplateSave()
    {
        return $this->isTemplateSave;
    }

    /**
     * Set value to check if it is template saving or not
     *
     * @param bool
     * @return void
     */
    public function setIsTemplateSave($value)
    {
        $this->isTemplateSave = (bool)$value;
    }
}
