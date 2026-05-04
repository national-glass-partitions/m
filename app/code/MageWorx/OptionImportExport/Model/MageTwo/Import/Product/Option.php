<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionImportExport\Model\MageTwo\Import\Product;

use Exception;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Data;
use Magento\Catalog\Model\Product\Option\Value;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product\Option\Collection;
use Magento\Catalog\Model\ResourceModel\Product\Option\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\ImportExport\Model\ResourceModel\CollectionByPagesIteratorFactory;
use Magento\ImportExport\Model\ResourceModel\Helper;
use Magento\Store\Model\Store;
use Magento\CatalogImportExport\Model\Import\Product;
use Magento\Catalog\Model\ResourceModel\Product\Option\Value\Collection as ProductOptionValueCollection;
use Magento\Catalog\Model\ResourceModel\Product\Option\Value\CollectionFactory as ProductOptionValueCollectionFactory;
use Magento\ImportExport\Model\Import as ImportModel;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\Store\Model\StoreManagerInterface;
use MageWorx\OptionBase\Api\AttributeInterface;
use MageWorx\OptionBase\Api\ImportInterface;
use Psr\Log\LoggerInterface as Logger;
use MageWorx\OptionBase\Model\ResourceModel\DataSaver;
use MageWorx\OptionBase\Model\AttributeSaver;
use MageWorx\OptionBase\Model\Product\Attributes as ProductAttributes;
use MageWorx\OptionBase\Model\Product\Option\Attributes as OptionAttributes;
use MageWorx\OptionBase\Model\Product\Option\Value\Attributes as ValueAttributes;
use MageWorx\OptionBase\Model\ProductAttributes as ProductAttributesEntity;
use MageWorx\OptionTemplates\Model\ResourceModel\Group as GroupResourceModel;
use MageWorx\OptionBase\Helper\Data as BaseHelper;

class Option extends Product\Option
{
    private string $productEntityLinkField = '';
    private string $productEntityIdentifierField = '';
    private ProductOptionValueCollectionFactory $productOptionValueCollectionFactory;
    private array $optionTypeTitles = [];
    private array $optionTypeTitleIds = [];
    private array $lastOptionTitle = [];
    protected array $allGroupsData = [];
    protected ProductAttributes $productAttributes;
    protected OptionAttributes $optionAttributes;
    protected ValueAttributes $valueAttributes;
    protected AttributeSaver $attributeSaver;
    protected MessageManager $messageManager;
    protected ManagerInterface $eventManager;
    protected Logger $logger;
    protected DataSaver $dataSaver;
    protected GroupResourceModel $groupResourceModel;
    protected array $missingOptionTemplates = [];
    protected array $optionIdsMap = [];
    protected array $valueIdsMap = [];
    protected array $convertedOptionIdsMap = [];
    protected array $convertedValueIdsMap = [];
    protected array $currentProductData = [];
    protected array $nextProductData = [];
    protected BaseHelper $baseHelper;

    protected array $defaultOptionColumns = [
        'custom_option_id',
        'custom_option_title',
        'custom_option_type',
        'custom_option_is_required',
        'custom_option_price',
        'custom_option_price_type',
        'custom_option_sku',
        'custom_option_sort_order',
        'custom_option_file_extension',
        'custom_option_image_size_x',
        'custom_option_image_size_y',
        'custom_option_max_characters',
        'custom_option_group_id'
    ];

    protected array $defaultValueColumns = [
        'custom_option_row_id',
        'custom_option_row_title',
        'custom_option_row_price',
        'custom_option_row_price_type',
        'custom_option_row_sku',
        'custom_option_row_sort',
        'custom_option_row_group_id'
    ];

    /**
     * @param GroupResourceModel $groupResourceModel
     * @param ManagerInterface $eventManager
     * @param \Magento\ImportExport\Model\ResourceModel\Import\Data $importData
     * @param ResourceConnection $resource
     * @param AttributeSaver $attributeSaver
     * @param Logger $logger
     * @param MessageManager $messageManager
     * @param DataSaver $dataSaver
     * @param Helper $resourceHelper
     * @param StoreManagerInterface $_storeManager
     * @param ProductFactory $productFactory
     * @param CollectionFactory $optionColFactory
     * @param CollectionByPagesIteratorFactory $colIteratorFactory
     * @param Data $catalogData
     * @param ScopeConfigInterface $scopeConfig
     * @param TimezoneInterface $dateTime
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @param array $data
     * @param ProductOptionValueCollectionFactory $productOptionValueCollectionFactory
     * @throws LocalizedException
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        GroupResourceModel $groupResourceModel,
        ManagerInterface $eventManager,
        \Magento\ImportExport\Model\ResourceModel\Import\Data $importData,
        ResourceConnection $resource,
        AttributeSaver $attributeSaver,
        Logger $logger,
        MessageManager $messageManager,
        DataSaver $dataSaver,
        Helper $resourceHelper,
        StoreManagerInterface $_storeManager,
        ProductFactory $productFactory,
        CollectionFactory $optionColFactory,
        CollectionByPagesIteratorFactory $colIteratorFactory,
        Data $catalogData,
        ScopeConfigInterface $scopeConfig,
        TimezoneInterface $dateTime,
        ProcessingErrorAggregatorInterface $errorAggregator,
        ProductAttributes $productAttributes,
        OptionAttributes $optionAttributes,
        ValueAttributes $valueAttributes,
        BaseHelper $baseHelper,
        array $data = [],
        ProductOptionValueCollectionFactory $productOptionValueCollectionFactory = null
    ) {
        $this->groupResourceModel = $groupResourceModel;
        $this->attributeSaver     = $attributeSaver;
        $this->productAttributes  = $productAttributes;
        $this->optionAttributes   = $optionAttributes;
        $this->valueAttributes    = $valueAttributes;
        $this->logger             = $logger;
        $this->messageManager     = $messageManager;
        $this->eventManager       = $eventManager;
        $this->dataSaver          = $dataSaver;
        $this->baseHelper         = $baseHelper;
        parent::__construct(
            $importData,
            $resource,
            $resourceHelper,
            $_storeManager,
            $productFactory,
            $optionColFactory,
            $colIteratorFactory,
            $catalogData,
            $scopeConfig,
            $dateTime,
            $errorAggregator,
            $data,
            $productOptionValueCollectionFactory
        );
        $this->productOptionValueCollectionFactory = $productOptionValueCollectionFactory
            ?: ObjectManager::getInstance()->get(ProductOptionValueCollectionFactory::class);
    }

    /**
     * Import data rows
     *
     * @return boolean
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @throws FileSystemException
     */
    protected function _importData()
    {
        $this->initProductsSku();
        $this->allGroupsData = $this->groupResourceModel->getAllGroupsData();

        $nextOptionId = $this->_resourceHelper->getNextAutoincrement(
            $this->_tables['catalog_product_option']
        );
        $nextValueId  = $this->_resourceHelper->getNextAutoincrement(
            $this->_tables['catalog_product_option_type_value']
        );

        $prevOptionId            = 0;
        $optionId                = null;
        $valueId                 = null;
        $productId               = 0;
        $productIds              = [];
        $firstIterationBunchFlag = true;


        $bunch = $this->getTheNextBunch();
        if (!$bunch) { //first line can be empty

            $bunch = $this->getTheNextBunch();
        }

        while ($bunch) {
            $products        = [];
            $options         = [];
            $titles          = [];
            $prices          = [];
            $typeValues      = [];
            $typePrices      = [];
            $typeTitles      = [];
            $typeTitleIds    = [];
            $parentCount     = [];
            $childCount      = [];
            $customOptions   = [];
            $lastProdInBunch = $bunchRowsCount = count($bunch);

            $reverseBunch = array_reverse($bunch, true);

            foreach ($reverseBunch as $rowNumber => $rowData) {
                if ($rowData['sku']) {
                    $lastProdInBunch = $rowNumber;
                    break;
                }
            }

            if ($firstIterationBunchFlag && $lastProdInBunch == 0) {
                $lastProdInBunch = $bunchRowsCount;
            }

            if (!$firstIterationBunchFlag && !empty($this->nextProductData)) {
                $this->currentProductData = $this->nextProductData;
                $this->nextProductData    = [];
            }

            $this->currentProductData = array_merge(
                $this->currentProductData,
                array_values(array_slice($bunch, 0, $lastProdInBunch))
            );
            $this->nextProductData    = array_merge(
                $this->nextProductData,
                array_values(array_slice($bunch, $lastProdInBunch, $bunchRowsCount))
            );

            if (empty($this->currentProductData)) {
                $this->currentProductData = $this->nextProductData;
            }

            $bunch = $this->getTheNextBunch();

            if ($bunch && empty($this->nextProductData)) {
                $firstIterationBunchFlag = false;
                continue;
            } elseif (!$bunch) {
                $this->currentProductData = array_merge(
                    $this->currentProductData,
                    array_values($this->nextProductData)
                );
            }

            foreach ($this->currentProductData as $rowNumber => $rowData) {
                if ($this->skipOptionSave($rowData)) {
                    return true;
                }

                if (isset($optionId, $valueId) && empty($rowData[Product::COL_STORE_VIEW_CODE])) {
                    $nextOptionId = $optionId;
                    $nextValueId  = $valueId;
                }
                $optionId = $nextOptionId;
                $valueId  = $nextValueId;
                if (!empty($rowData[self::COLUMN_SKU]) && isset($this->_productsSkuToId[$rowData[self::COLUMN_SKU]])) {
                    $this->_rowProductId = $this->_productsSkuToId[$rowData[self::COLUMN_SKU]];
                }

                $optionData   = $this->convertCsvFormat($rowData);
                $combinedData = array_merge($rowData, $optionData);
                if (!$this->isRowAllowedToImport($combinedData, $rowNumber)) {
                    continue;
                }

                $nextProductId = $this->parseProductId($combinedData);
                if ($nextProductId) {
                    $productId = $nextProductId;
                }
                if (!$productId) {
                    continue;
                }

                $productIds[$productId] = $productId;

                $customOptions[$productId][] = $combinedData;
            }

            foreach ($customOptions as $productId => $multiRowData) {
                foreach ($multiRowData as $combinedData) {
                    $this->_rowProductId = $productId;
                    if (!$this->_parseRequiredData($combinedData)) {
                        continue;
                    }

                    $optionData = $this->collectOptionMainData(
                        $combinedData,
                        $prevOptionId,
                        $optionId,
                        $products,
                        $prices
                    );

                    if ($optionData != null) {
                        $options[] = $optionData;
                    }
                    $this->collectOptionTypeData(
                        $combinedData,
                        $prevOptionId,
                        $valueId,
                        $typeValues,
                        $typePrices,
                        $typeTitles,
                        $typeTitleIds,
                        $parentCount,
                        $childCount
                    );

                    $this->_collectOptionTitle($combinedData, $prevOptionId, $titles);
                    $this->checkOptionTitles(
                        $options,
                        $titles,
                        $combinedData,
                        $prevOptionId,
                        $optionId,
                        $products,
                        $prices
                    );

                    $combinedData['product_id'] = $productId;

                    $valueAttributes = $this->valueAttributes->getData();
                    $this->collectAttributeData($valueAttributes, $combinedData);

                    $optionAttributes = $this->optionAttributes->getData();
                    $this->collectAttributeData($optionAttributes, $combinedData);

                    $this->collectProductAttributeData($combinedData);
                }
            }

            $this->removeExistingOptions($products);
            $types = [
                'values'   => $typeValues,
                'prices'   => $typePrices,
                'titles'   => $typeTitles,
                'titleIds' => $typeTitleIds
            ];
            $this->setLastOptionTitle($titles);

            $this->savePreparedCustomOptions(
                $products,
                $options,
                $titles,
                $prices,
                $types
            );
            $this->saveMageWorxAttributes();

            $this->currentProductData = [];
            $firstIterationBunchFlag  = false;
        }

        $this->removeOptionsFromMissingTemplates($productIds);
        $this->logMissingOptionTemplates();

        $this->eventManager->dispatch(
            'mageworx_optionimportexport_product_magetwo_import_after',
            ['product_ids' => $productIds]
        );

        return true;
    }

    /**
     * Remove options from missing templates
     *
     * @param array $productIds
     * @return void
     */
    protected function removeOptionsFromMissingTemplates($productIds)
    {
        foreach ($productIds as $productId) {
            $this->groupResourceModel->removeIncorrectlyLinkedOptions($productId);
        }
    }

    /**
     * Log missing option templates data to have opportunity to apply templates manually to necessary products
     *
     * @return void
     */
    protected function logMissingOptionTemplates()
    {
        if ($this->missingOptionTemplates) {
            $this->messageManager->addWarningMessage(
                __('There are some missing option templates. Please, see "var/log/system.log" for further information')
            );
        }
        foreach ($this->missingOptionTemplates as $missingOptionTemplate) {
            $message = __(
                "Option Template '%1' is missing, linked Product SKUs: %2",
                $missingOptionTemplate['title'],
                implode(',', $missingOptionTemplate['product_skus'])
            );
            $this->logger->warning($message);
        }
    }

    /**
     * Check if data contains MageWorx fields
     * if not - this is standard magento export file and we should skip the option import process to avoid APO data loss
     *
     * @param array $rowData
     * @return boolean
     */
    protected function skipOptionSave($rowData)
    {
        try {
            return !(isset($rowData['option_templates']) || $rowData['option_templates'] === null);
        } catch (Exception $e) {
            return true;
        }
    }

    /**
     * Save MageWorx attributes
     *
     * @return void
     */
    protected function saveMageWorxAttributes()
    {
        $this->_resource->getConnection()->beginTransaction();
        try {
            $collectedData = $this->attributeSaver->getAttributeData();
            try {
                $this->convertOptionIds($collectedData);
            } catch (Exception $e) {
                $this->messageManager->addErrorMessage(__('Something goes wrong with option IDs conversion'));
                throw $e;
            }
            $this->attributeSaver->deleteOldAttributeData($collectedData, 'product');
            foreach ($collectedData as $tableName => $dataArray) {
                if (empty($dataArray['save'])) {
                    continue;
                }
                $this->dataSaver->insertMultipleData($tableName, $dataArray['save']);
            }
            $this->_resource->getConnection()->commit();
        } catch (Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __("Something went wrong while saving product's APO attributes")
            );
            $this->logger->critical($e->getMessage());
            $this->_resource->getConnection()->rollBack();
        }
        $this->attributeSaver->clearAttributeData();
    }

    /**
     * Collect product attributes
     *
     * @param array $row
     * @return void
     */
    protected function collectProductAttributeData($row)
    {
        if (empty($row['sku']) || !isset($row['custom_option_title'])) {
            return;
        }

        $productAttributes = $this->productAttributes->getData();
        if (!$productAttributes || !is_array($productAttributes)) {
            return;
        }

        $data = [];
        foreach ($productAttributes as $productAttribute) {
            $attributeData = $productAttribute->collectImportDataMageTwo($row);
            if (!$attributeData) {
                continue;
            }

            if (!empty($attributeData['delete'])) {
                foreach ($attributeData['delete'] as $attributeDatum) {
                    $data['delete'][$row['product_id']] = $attributeDatum;
                }
            }

            if (empty($attributeData['save'])) {
                continue;
            }
            foreach ($attributeData['save'] as $attributeDatum) {
                if (!isset($data['save'][$row['product_id']])) {
                    $data['save'][$row['product_id']] = $attributeDatum;
                } else {
                    $data['save'][$row['product_id']] = array_merge(
                        $data['save'][$row['product_id']],
                        $attributeDatum
                    );
                }
            }
            $data['save'][$row['product_id']]['product_id'] = $row['product_id'];
        }

        if (!empty($row['option_templates'])) {
            $this->groupResourceModel->removeProductRelations($row['product_id']);
            $groupData = explode('|', $row['option_templates']);
            foreach ($groupData as $groupDatum) {
                $groupDatumParts = explode('=', $groupDatum);
                if (!is_array($groupDatumParts) || count($groupDatumParts) !== 2) {
                    continue;
                }
                $groupTitle = str_replace('&separator', '|', $groupDatumParts[1]);
                $groupTitle = str_replace('&equal', '=', $groupTitle);

                if (isset($this->allGroupsData[$groupDatumParts[0]])
                    && $this->allGroupsData[$groupDatumParts[0]] === $groupTitle
                ) {
                    $this->groupResourceModel->addProductRelation($groupDatumParts[0], $row['product_id']);
                } else {
                    $this->missingOptionTemplates[$groupDatumParts[0]]['title']          = $groupTitle;
                    $this->missingOptionTemplates[$groupDatumParts[0]]['product_skus'][] = $row['sku'];
                }
            }
        }

        $tableName = $this->_resource->getTableName(ProductAttributesEntity::TABLE_NAME);
        $this->attributeSaver->addAttributeData($tableName, $data);
    }

    /**
     * Convert option IDs from old to new values
     *
     * @param array $collectedData
     * @return array|null
     */
    protected function convertOptionIds(&$collectedData)
    {
        $valueKeys    = ['option_type_id', 'dp_child_option_type_id', 'dp_parent_option_type_id'];
        $optionKeys   = ['option_id', 'dp_child_option_id', 'dp_parent_option_id'];
        $valueIdsMap  = $this->valueIdsMap;
        $optionIdsMap = $this->optionIdsMap;
        foreach ($collectedData as $tableName => &$dataArray) {
            if (!empty($dataArray['delete'])) {
                foreach ($dataArray['delete'] as $key => $data) {
                    if (isset($data['option_type_id'])) {
                        $prevValueId = $dataArray['delete'][$key]['option_type_id'];
                        if (!empty($this->convertedValueIdsMap[$prevValueId])) {
                            $dataArray['delete'][$key]['option_type_id'] = $this->convertedValueIdsMap[$prevValueId];
                        } elseif (!empty($valueIdsMap[$prevValueId])) {
                            $dataArray['delete'][$key]['option_type_id'] = $valueIdsMap[$prevValueId];
                        }
                    }
                    if (isset($data['option_id'])) {
                        $prevOptionId = $dataArray['delete'][$key]['option_id'];
                        if (!empty($this->convertedOptionIdsMap[$prevOptionId])) {
                            $dataArray['delete'][$key]['option_id'] = $this->convertedOptionIdsMap[$prevOptionId];
                        } elseif (!empty($optionIdsMap[$prevOptionId])) {
                            $dataArray['delete'][$key]['option_id'] = $optionIdsMap[$prevOptionId];
                        }
                    }
                }
            }
            if (!empty($dataArray['save'])) {
                foreach ($dataArray['save'] as $key => $data) {
                    foreach ($valueKeys as $valueKey) {
                        if (!isset($data[$valueKey])) {
                            continue;
                        }
                        if ($valueKey === 'dp_child_option_type_id' && empty($data[$valueKey])) {
                            continue;
                        }
                        $prevValueId = $dataArray['save'][$key][$valueKey];
                        if (!empty($this->convertedValueIdsMap[$prevValueId])) {
                            $dataArray['save'][$key][$valueKey] = $this->convertedValueIdsMap[$prevValueId];
                        } elseif (!empty($valueIdsMap[$prevValueId])) {
                            $dataArray['save'][$key][$valueKey] = $valueIdsMap[$prevValueId];
                        }
                    }
                    foreach ($optionKeys as $optionKey) {
                        if (!isset($data[$optionKey])) {
                            continue;
                        }
                        $prevOptionId = $dataArray['save'][$key][$optionKey];
                        if (!empty($this->convertedOptionIdsMap[$prevOptionId])) {
                            $dataArray['save'][$key][$optionKey] = $this->convertedOptionIdsMap[$prevOptionId];
                        } elseif (!empty($optionIdsMap[$prevOptionId])) {
                            $dataArray['save'][$key][$optionKey] = $optionIdsMap[$prevOptionId];
                        }
                    }
                }
            }
        }
    }

    /**
     * Collect custom option main data to import
     *
     * @param array $rowData
     * @param int &$prevOptionId
     * @param int &$nextOptionId
     * @param array &$products
     * @param array &$prices
     * @return array|null
     */
    protected function collectOptionMainData(
        array &$rowData,
        &$prevOptionId,
        &$nextOptionId,
        array &$products,
        array &$prices
    ) {
        $optionData = null;

        if ($this->_rowIsMain) {
            $optionData = empty($rowData[Product::COL_STORE_VIEW_CODE])
                ? $this->_getOptionData($rowData, $this->_rowProductId, $nextOptionId, $this->_rowType)
                : '';

            if (!$this->_isRowHasSpecificType($this->_rowType)
                && ($priceData = $this->_getPriceData($rowData, $nextOptionId, $this->_rowType))
            ) {
                if ($this->_isPriceGlobal) {
                    $prices[$nextOptionId][Store::DEFAULT_STORE_ID] = $priceData;
                } else {
                    $prices[$nextOptionId][$this->_rowStoreId] = $priceData;
                }
            }

            if (!isset($products[$this->_rowProductId])) {
                $products[$this->_rowProductId] = $this->_getProductData($rowData, $this->_rowProductId);
            }

            if (empty($rowData['custom_option_id'])) {
                $rowData['custom_option_id'] = (int)$nextOptionId;
            }
            $this->optionIdsMap[$rowData['custom_option_id']] = (int)$nextOptionId;

            $prevOptionId = $nextOptionId++;
        }

        return $optionData;
    }

    /**
     * Collect custom option type data to import
     *
     * @param array $rowData
     * @param int &$prevOptionId
     * @param int &$nextValueId
     * @param array &$typeValues
     * @param array &$typePrices
     * @param array &$typeTitles
     * @param array &$typeTitleIds
     * @param array &$parentCount
     * @param array &$childCount
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function collectOptionTypeData(
        array &$rowData,
        &$prevOptionId,
        &$nextValueId,
        array &$typeValues,
        array &$typePrices,
        array &$typeTitles,
        array &$typeTitleIds,
        array &$parentCount,
        array &$childCount
    ) {
        if ($this->_isRowHasSpecificType($this->_rowType) && $prevOptionId) {
            if (empty($rowData['custom_option_row_id'])) {
                $rowData['custom_option_row_id'] = (int)$nextValueId;
            }
            $this->valueIdsMap[$rowData['custom_option_row_id']] = (int)$nextValueId;

            $specificTypeData = $this->_getSpecificTypeData($rowData, $nextValueId);
            if ($specificTypeData) {
                $typeValues[$prevOptionId][] = $specificTypeData['value'];
                if (isset($specificTypeData['value']['option_type_title_id'])) {
                    $typeTitleIds[$nextValueId] = $specificTypeData['value']['option_type_title_id'];
                } else {
                    $typeTitleIds[$nextValueId] = '';
                }

                if (!isset($typeTitles[$nextValueId][Store::DEFAULT_STORE_ID])) {
                    $typeTitles[$nextValueId][Store::DEFAULT_STORE_ID] = $specificTypeData['title'];
                }

                if ($specificTypeData['price']) {
                    if ($this->_isPriceGlobal) {
                        $typePrices[$nextValueId][Store::DEFAULT_STORE_ID] = $specificTypeData['price'];
                    } else {
                        if (!isset($typePrices[$nextValueId][Store::DEFAULT_STORE_ID])) {
                            $typePrices[$nextValueId][Store::DEFAULT_STORE_ID] = $specificTypeData['price'];
                        }
                        $typePrices[$nextValueId][$this->_rowStoreId] = $specificTypeData['price'];
                    }
                }

                $nextValueId++;
            }
            $specificTypeData = $this->_getSpecificTypeData($rowData, 0, false);
            if ($specificTypeData) {
                if (isset($specificTypeData['price'])) {
                    $typePrices[$nextValueId][$this->_rowStoreId] = $specificTypeData['price'];
                }
                if (isset($specificTypeData['value']['option_type_title_id'])) {
                    $typeTitleIds[$nextValueId] = $specificTypeData['value']['option_type_title_id'];
                } else {
                    $typeTitleIds[$nextValueId] = '';
                }
                $typeTitles[$nextValueId++][$this->_rowStoreId] = $specificTypeData['title'];
            }
        }
    }

    /**
     * Check options titles.
     *
     * If products were split up between bunches,
     * this function will add needed option for option titles
     *
     * @param array $options
     * @param array $titles
     * @param array $combinedData
     * @param int $prevOptionId
     * @param int $optionId
     * @param array $products
     * @param array $prices
     * @return void
     */
    private function checkOptionTitles(
        array &$options,
        array &$titles,
        array $combinedData,
        int &$prevOptionId,
        int &$optionId,
        array $products,
        array $prices
    ): void {
        $titlesCount = count($titles);
        if ($titlesCount > 0 && count($options) !== $titlesCount) {
            $combinedData[Product::COL_STORE_VIEW_CODE] = '';
            $optionId--;
            $option = $this->_collectOptionMainData(
                $combinedData,
                $prevOptionId,
                $optionId,
                $products,
                $prices
            );
            if ($option) {
                $options[] = $option;
            }
        }
    }

    /**
     * Setting last Custom Option Title
     * to use it later in _collectOptionTitle
     * to set correct title for default store view
     *
     * @param array $titles
     */
    private function setLastOptionTitle(array &$titles): void
    {
        if (count($titles) > 0) {
            end($titles);
            $key                         = key($titles);
            $this->lastOptionTitle[$key] = $titles[$key];
        }
    }

    /**
     * Save prepared custom options.
     *
     * @param array $products
     * @param array $options
     * @param array $titles
     * @param array $prices
     * @param array $types
     *
     * @return void
     */
    private function savePreparedCustomOptions(
        array $products,
        array $options,
        array $titles,
        array $prices,
        array $types
    ): void {
        if ($this->getBehavior() == ImportModel::BEHAVIOR_APPEND) {
            $this->_compareOptionsWithExisting($options, $titles, $prices, $types['values']);
            $this->restoreOriginalOptionTypeIds(
                $types['values'],
                $types['prices'],
                $types['titles'],
                $types['titleIds']
            );
        }

        $this->_resource->getConnection()->beginTransaction();
        try {
            if ($this->_isReadyForSaving($options, $titles, $types['values'])) {
                $this->_saveOptions($options);
            }

            $this->_saveTitles($titles);
            $this->_savePrices($prices);
            $this->_saveSpecificTypeValues($types['values']);
            $this->_saveSpecificTypePrices($types['prices']);
            $this->_saveSpecificTypeTitles($types['titles']);
            $this->deleteSpecificOptions($titles, $products);
            $this->_updateProducts($products);
            $this->_resource->getConnection()->commit();
        } catch (Exception $e) {
            $this->_resource->getConnection()->rollback();
            throw($e);
        }
    }

    /**
     * remove Options which were modified in imported data
     *
     * @param $products
     * @param $titles
     * @return $this
     */
    private function deleteSpecificOptions($titles, $products)
    {
        $optionIds  = array_keys($titles);
        $productIds = array_keys($products);
        foreach ($productIds as $productId) {
            $this->_connection->delete(
                $this->_tables['catalog_product_option'],
                $this->_connection->quoteInto(
                    'product_id = ' . $productId .
                    ' AND option_id NOT IN (?)',
                    $optionIds
                )
            );
        }

        return $this;
    }

    /**
     * Remove existing options.
     *
     * Remove all existing options if import behaviour is not APPEND
     *
     * @param array $products
     *
     * @return void
     */
    private function removeExistingOptions(array $products): void
    {
        if ($this->getBehavior() != ImportModel::BEHAVIOR_APPEND) {
            $this->_deleteEntities(array_keys($products));
        }
    }

    /**
     * Restore original IDs for existing option types.
     *
     * Warning: some arguments are modified by reference
     *
     * @param array $typeValues
     * @param array $typePrices
     * @param array $typeTitles
     * @param array $typeTitleIds
     * @return void
     */
    private function restoreOriginalOptionTypeIds(
        array &$typeValues,
        array &$typePrices,
        array &$typeTitles,
        array $typeTitleIds
    ) {
        $valueIdsMap = array_flip($this->valueIdsMap);

        foreach ($typeValues as $optionId => &$optionTypes) {
            foreach ($optionTypes as &$optionType) {
                $optionTypeId = $optionType['option_type_id'];

                foreach ($typeTitles[$optionTypeId] as $storeId => $optionTypeTitle) {
                    $existingTypeId = $this->getExistingOptionTypeId(
                        $optionId,
                        $storeId,
                        $optionTypeTitle,
                        $typeTitleIds[$optionTypeId]
                    );
                    if ($existingTypeId) {
                        if (isset($valueIdsMap[$optionType['option_type_id']])) {
                            $this->convertedValueIdsMap[$valueIdsMap[$optionType['option_type_id']]] = (int)$existingTypeId;
                        }
                        $optionType['option_type_id'] = $existingTypeId;
                        $typeTitles[$existingTypeId]  = $typeTitles[$optionTypeId];
                        unset($typeTitles[$optionTypeId]);
                        if (isset($typePrices[$optionTypeId])) {
                            $typePrices[$existingTypeId] = $typePrices[$optionTypeId];
                            unset($typePrices[$optionTypeId]);
                        }
                        break;
                    }
                }
            }
        }
    }

    /**
     * Find options with the same titles in DB
     *
     * @return array
     * phpcs:disable Generic.Metrics.NestingLevel
     */
    protected function _findOldOptionsWithTheSameTitles()
    {
        $errorRows = [];
        foreach ($this->_newOptionsOldData as $productId => $options) {
            foreach ($options as $outerData) {
                if (isset($this->getOldCustomOptions()[$productId])) {
                    $optionsCount = 0;
                    foreach ($this->getOldCustomOptions()[$productId] as $innerData) {
                        if (count($outerData['titles']) == count($innerData['titles'])) {
                            $outerTitles = $outerData['titles'];
                            $innerTitles = $innerData['titles'];
                            ksort($outerTitles);
                            ksort($innerTitles);
                            if ($outerTitles === $innerTitles && $outerData['titleId'] === $innerData['titleId']) {
                                $optionsCount++;
                            }
                        }
                    }
                    if ($optionsCount > 1) {
                        foreach ($outerData['rows'] as $dataRow) {
                            $errorRows[] = $dataRow;
                        }
                    }
                }
            }
        }
        sort($errorRows);

        return $errorRows;
    }

    /**
     * Save validated option data
     *
     * @param array $rowData
     * @param int $rowNumber
     * @return void
     */
    protected function _saveNewOptionData(array $rowData, $rowNumber)
    {
        if (!empty($rowData[self::COLUMN_SKU])) {
            $this->_rowProductSku = $rowData[self::COLUMN_SKU];
        }
        if (!empty($rowData[self::COLUMN_TYPE])) {
            $this->_newCustomOptionId++;
        }
        if (!empty($rowData[self::COLUMN_STORE])) {
            $storeCode = $rowData[self::COLUMN_STORE];
            $storeId   = $this->_storeCodeToId[$storeCode];
        } else {
            $storeId = Store::DEFAULT_STORE_ID;
        }
        if (isset($this->_productsSkuToId[$this->_rowProductSku])) {
            $productId = $this->_productsSkuToId[$this->_rowProductSku];
            if (!isset($this->_newOptionsOldData[$productId])) {
                $this->_newOptionsOldData[$productId] = [];
            }
            if (!isset($this->_newOptionsOldData[$productId][$this->_newCustomOptionId])) {
                $this->_newOptionsOldData[$productId][$this->_newCustomOptionId] = [
                    'titles'  => [],
                    'titleId' => '',
                    'rows'    => [],
                    'type'    => $rowData[self::COLUMN_TYPE],
                ];
            }
            $this->_newOptionsOldData[$productId][$this
                ->_newCustomOptionId]['titles'][$storeId] = $rowData[self::COLUMN_TITLE];
            if (isset($rowData['custom_option_option_title_id'])) {
                $this->_newOptionsOldData[$productId][$this->_newCustomOptionId]['titleId'] = $rowData['custom_option_option_title_id'];
            }
            $this->_newOptionsOldData[$productId][$this->_newCustomOptionId]['rows'][] = $rowNumber;
        } else {
            $productSku = $this->_rowProductSku;
            if (!isset($this->_newOptionsNewData[$this->_rowProductSku])) {
                $this->_newOptionsNewData[$this->_rowProductSku] = [];
            }
            if (!isset($this->_newOptionsNewData[$productSku][$this->_newCustomOptionId])) {
                $this->_newOptionsNewData[$productSku][$this->_newCustomOptionId] = [
                    'titles'  => [],
                    'titleId' => '',
                    'rows'    => [],
                    'type'    => $rowData[self::COLUMN_TYPE],
                ];
            }
            $this->_newOptionsNewData[$productSku][$this
                ->_newCustomOptionId]['titles'][$storeId] = $rowData[self::COLUMN_TITLE];
            if (isset($rowData['custom_option_option_title_id'])) {
                $this->_newOptionsOldData[$productSku][$this->_newCustomOptionId]['titleId'] = $rowData['custom_option_option_title_id'];
            }
            $this->_newOptionsNewData[$productSku][$this->_newCustomOptionId]['rows'][] = $rowNumber;
        }
    }

    /**
     * Identify ID of the provided option type by its title in the specified store.
     *
     * @param int $optionId
     * @param int $storeId
     * @param string $optionTypeTitle
     * @param string $optionTypeTitleId
     * @return int|null
     */
    private function getExistingOptionTypeId($optionId, $storeId, $optionTypeTitle, $optionTypeTitleId)
    {
        if (!isset($this->optionTypeTitles[$storeId])) {
            $this->optionTypeTitleIds[$storeId] = [];
            /** @var ProductOptionValueCollection $optionTypeCollection */
            $optionTypeCollection = $this->productOptionValueCollectionFactory->create();
            $optionTypeCollection->addTitleToResult($storeId);
            /** @var Value $type */
            foreach ($optionTypeCollection as $type) {
                $this->optionTypeTitles[$storeId][$type->getOptionId()][$type->getId()]   = $type->getTitle();
                $this->optionTypeTitleIds[$storeId][$type->getOptionId()][$type->getId()] = $type->getOptionTypeTitleId(
                );
            }
        }
        if (isset($this->optionTypeTitles[$storeId][$optionId])
            && is_array($this->optionTypeTitles[$storeId][$optionId])
        ) {
            foreach ($this->optionTypeTitles[$storeId][$optionId] as $optionTypeId => $currentTypeTitle) {
                if (isset($this->optionTypeTitleIds[$storeId][$optionId][$optionTypeId])) {
                    if ($optionTypeTitle === $currentTypeTitle
                        && $optionTypeTitleId === $this->optionTypeTitleIds[$storeId][$optionId][$optionTypeId]
                        && !in_array($optionTypeId, $this->convertedValueIdsMap)
                    ) {
                        return $optionTypeId;
                    }
                } elseif ($optionTypeTitle === $currentTypeTitle
                    && !in_array($optionTypeId, $this->convertedValueIdsMap)
                ) {
                    return $optionTypeId;
                }
            }
        }

        return null;
    }

    /**
     * Collect attribute data
     *
     * @param array $attributes
     * @param array $combinedData
     * @return void
     */
    protected function collectAttributeData($attributes, $combinedData)
    {
        foreach ($attributes as $attribute) {
            $data = [];
            if (!$attribute->hasOwnTable()) {
                continue;
            }
            $attributeItemData = $attribute->collectImportDataMageTwo($combinedData);
            if (!$attributeItemData) {
                continue;
            }
            $tableName = $this->_resource->getTableName($attribute->getTableName('product'));

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
     * Convert csv format to help Magento parse required fields
     *
     * @param array $rowData
     * @return array
     */
    protected function convertCsvFormat($rowData)
    {
        $customOptions = [];
        foreach ($this->defaultOptionColumns as $columnName) {
            $customOptions['_' . $columnName] = $rowData[$columnName];
        }
        foreach ($this->defaultValueColumns as $columnName) {
            $customOptions['_' . $columnName] = $rowData[$columnName];
        }

        return $customOptions;
    }

    /**
     * Retrieve option data
     *
     * @param array $rowData
     * @param int $productId
     * @param int $optionId
     * @param string $type
     * @return array
     */
    protected function _getOptionData(array $rowData, $productId, $optionId, $type)
    {
        $optionData = [
            'option_id'       => $optionId,
            'sku'             => '',
            'max_characters'  => 0,
            'file_extension'  => null,
            'image_size_x'    => 0,
            'image_size_y'    => 0,
            'product_id'      => $productId,
            'type'            => $type,
            'group_option_id' => empty($rowData['custom_option_group_id']) ? null : $rowData['custom_option_group_id'],
            'is_require'      => empty($rowData[self::COLUMN_IS_REQUIRED]) ? 0 : 1,
            'sort_order'      => empty($rowData[self::COLUMN_SORT_ORDER]) ? 0 : abs(
                $rowData[self::COLUMN_SORT_ORDER]
            ),
        ];

        $optionAttributes = $this->optionAttributes->getData();
        foreach ($optionAttributes as $optionAttribute) {
            /** @var AttributeInterface|ImportInterface $optionAttribute */
            if ($optionAttribute->hasOwnTable()) {
                continue;
            }
            $optionData[$optionAttribute->getName()] = $optionAttribute->prepareImportDataMageTwo($rowData, 'option');
        }

        if (!$this->_isRowHasSpecificType($type)) {
            foreach ($this->_specificTypes[$type] as $paramSuffix) {
                if (isset($rowData[self::COLUMN_PREFIX . $paramSuffix])) {
                    $data = $rowData[self::COLUMN_PREFIX . $paramSuffix];

                    if (array_key_exists($paramSuffix, $optionData)) {
                        $optionData[$paramSuffix] = $data;
                    }
                }
            }
        }

        return $optionData;
    }

    /**
     * Retrieve specific type data
     *
     * @param array $rowData
     * @param int $optionTypeId
     * @param bool $defaultStore
     * @return array|false
     */
    protected function _getSpecificTypeData(array $rowData, $optionTypeId, $defaultStore = true)
    {
        $data                 = [];
        $priceData            = [];
        $customOptionRowPrice = $rowData[self::COLUMN_ROW_PRICE];
        if (!empty($customOptionRowPrice) || $customOptionRowPrice === '0') {
            $priceData['price']      = (double)rtrim($rowData[self::COLUMN_ROW_PRICE], '%');
            $priceData['price_type'] = ('%' == substr($rowData[self::COLUMN_ROW_PRICE], -1)) ? 'percent' : 'fixed';
        }
        if (!empty($rowData[self::COLUMN_ROW_TITLE]) && $defaultStore && empty($rowData[self::COLUMN_STORE])) {
            $valueData               = [
                'option_type_id'        => $optionTypeId,
                'sku'                   => !empty($rowData[self::COLUMN_ROW_SKU]) ? $rowData[self::COLUMN_ROW_SKU] : '',
                'group_option_value_id' => $rowData['custom_option_row_group_id']
            ];
            $valueData['sort_order'] = empty($rowData[self::COLUMN_ROW_SORT]) ? 0 : abs(
                $rowData[self::COLUMN_ROW_SORT]
            );

            $valueAttributes = $this->valueAttributes->getData();
            foreach ($valueAttributes as $valueAttribute) {
                if ($valueAttribute->hasOwnTable()) {
                    continue;
                }
                $valueData[$valueAttribute->getName()] = $valueAttribute->prepareImportDataMageTwo($rowData, 'value');
            }

            $data['value'] = $valueData;
            $data['title'] = $rowData[self::COLUMN_ROW_TITLE];
            $data['price'] = $priceData;
        } elseif (!empty($rowData[self::COLUMN_ROW_TITLE]) && !$defaultStore && !empty($rowData[self::COLUMN_STORE])) {
            if ($priceData) {
                $data['price'] = $priceData;
            }
            $data['title'] = $rowData[self::COLUMN_ROW_TITLE];
        }

        return $data ?: false;
    }

    /**
     * Save custom option type values
     *
     * @param array $typeValues Option type values
     * @param array $deletedValuesOptionIds Option IDs with deleted values (protects from deleting newly added values)
     * @return $this
     */
    protected function saveSpecificTypeValues(array $typeValues, &$deletedValuesOptionIds = [])
    {
        $typeValuesClone = $typeValues;
        foreach ($typeValuesClone as $optionId => $optionInfo) {
            if (in_array($optionId, $deletedValuesOptionIds)) {
                unset($typeValuesClone[$optionId]);
            }
        }
        if ($typeValuesClone) {
            $this->_deleteSpecificTypeValues(array_keys($typeValuesClone));
            foreach ($typeValuesClone as $optionId => $optionInfo) {
                $deletedValuesOptionIds[] = $optionId;
            }
        }

        $typeValueRows = [];
        foreach ($typeValues as $optionId => $optionInfo) {
            foreach ($optionInfo as $row) {
                $row['option_id'] = $optionId;
                $typeValueRows[]  = $row;
            }
        }
        if ($typeValueRows) {
            $this->_connection->insertMultiple($this->_tables['catalog_product_option_type_value'], $typeValueRows);
        }

        return $this;
    }

    /**
     * Find duplicated custom options and update existing options data
     *
     * @param array &$options
     * @param array &$titles
     * @param array &$prices
     * @param array &$typeValues
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function _compareOptionsWithExisting(array &$options, array &$titles, array &$prices, array &$typeValues)
    {
        $optionIdsMap = array_flip($this->optionIdsMap);

        foreach ($options as &$optionData) {
            $newOptionId = $optionData['option_id'];
            if ($optionId = $this->_findExistingOptionId($optionData, $titles[$newOptionId])) {
                if (isset($optionIdsMap[$newOptionId])) {
                    $this->convertedOptionIdsMap[$optionIdsMap[$newOptionId]] = (int)$optionId;
                }
                $optionData['option_id'] = $optionId;
                $titles[$optionId]       = $titles[$newOptionId];
                unset($titles[$newOptionId]);
                if (isset($prices[$newOptionId])) {
                    foreach ($prices[$newOptionId] as $storeId => $priceStoreData) {
                        $prices[$newOptionId][$storeId]['option_id'] = $optionId;
                    }
                }
                if (isset($typeValues[$newOptionId])) {
                    $typeValues[$optionId] = $typeValues[$newOptionId];
                    unset($typeValues[$newOptionId]);
                }
            }
        }

        return $this;
    }

    /**
     * Checks that option exists in DB
     *
     * @param array $newOptionData
     * @param array $newOptionTitles
     * @return bool|int
     */
    protected function _findExistingOptionId(array $newOptionData, array $newOptionTitles)
    {
        $productId = $newOptionData['product_id'];
        if (isset($this->getOldCustomOptions()[$productId])) {
            ksort($newOptionTitles);
            $existingOptions = $this->getOldCustomOptions()[$productId];
            foreach ($existingOptions as $optionId => $optionData) {
                if ($optionData['type'] == $newOptionData['type']
                    && $optionData['titles'][Store::DEFAULT_STORE_ID] == $newOptionTitles[Store::DEFAULT_STORE_ID]
                    && !in_array($optionId, $this->convertedOptionIdsMap)
                ) {
                    if (isset($newOptionData['option_title_id'])
                        && $optionData['titleId'] !== $newOptionData['option_title_id']
                    ) {
                        return false;
                    }

                    return $optionId;
                }
            }
        }

        return false;
    }

    /**
     * Load data of existed products
     *
     * @return $this
     */
    protected function initProductsSku()
    {
        $columns = ['entity_id', 'sku'];
        if ($this->getProductEntityLinkField() != $this->getProductIdentifierField()) {
            $columns[] = $this->getProductEntityLinkField();
        }
        foreach ($this->_productModel->getProductEntitiesInfo($columns) as $product) {
            $this->_productsSkuToId[$product['sku']] = $product[$this->getProductEntityLinkField()];
        }

        return $this;
    }

    /**
     * Load exiting custom options data
     *
     * @return $this
     */
    protected function _initOldCustomOptions()
    {
        if (!$this->_oldCustomOptions) {
            $oldCustomOptions = [];
            $optionTitleTable = $this->_tables['catalog_product_option_title'];
            foreach ($this->_storeCodeToId as $storeId) {
                $addCustomOptions = function (
                    \Magento\Catalog\Model\Product\Option $customOption
                ) use (
                    &$oldCustomOptions,
                    $storeId
                ) {
                    $productId = $customOption->getProductId();
                    if (!isset($oldCustomOptions[$productId])) {
                        $oldCustomOptions[$productId] = [];
                    }
                    if (isset($oldCustomOptions[$productId][$customOption->getId()])) {
                        $oldCustomOptions[$productId][$customOption->getId()]['titles'][$storeId] = $customOption
                            ->getTitle();
                        $oldCustomOptions[$productId][$customOption->getId()]['titleId']          = $customOption
                            ->getOptionTitleId();
                    } else {
                        $oldCustomOptions[$productId][$customOption->getId()] = [
                            'titles'  => [$storeId => $customOption->getTitle()],
                            'titleId' => $customOption->getOptionTitleId(),
                            'type'    => $customOption->getType(),
                        ];
                    }
                };
                /** @var $collection Collection */
                $this->_optionCollection->reset();
                $this->_optionCollection->getSelect()->join(
                    ['option_title' => $optionTitleTable],
                    'option_title.option_id = main_table.option_id',
                    ['title' => 'title', 'store_id' => 'store_id']
                )->where(
                    'option_title.store_id = ?',
                    $storeId
                );
                if (!empty($this->_newOptionsOldData)) {
                    $this->_optionCollection->addProductToFilter(array_keys($this->_newOptionsOldData));
                }

                $this->_byPagesIterator->iterate($this->_optionCollection, $this->_pageSize, [$addCustomOptions]);
            }
            $this->_oldCustomOptions = $oldCustomOptions;
        }

        return $this;
    }

    /**
     * Get existing custom options data
     *
     * @return array
     */
    private function getOldCustomOptions(): array
    {
        if ($this->_oldCustomOptions === null) {
            $this->_initOldCustomOptions();
        }

        return $this->_oldCustomOptions;
    }

    /**
     * @param array $rowData
     * @return bool
     */
    protected function parseProductId(array $rowData)
    {
        if (!empty($rowData[self::COLUMN_SKU]) && isset($this->_productsSkuToId[$rowData[self::COLUMN_SKU]])) {
            return $this->_productsSkuToId[$rowData[self::COLUMN_SKU]];
        } else {
            return false;
        }
    }

    /**
     * Get product entity link field
     *
     * @return string
     */
    private function getProductEntityLinkField(): string
    {
        if (!$this->productEntityLinkField) {
            $this->productEntityLinkField = $this->getMetadataPool()
                                                 ->getMetadata(ProductInterface::class)
                                                 ->getLinkField();
        }

        return (string)$this->productEntityLinkField;
    }

    /**
     * Get product entity identifier field
     *
     * @return string
     */
    private function getProductIdentifierField()
    {
        if (!$this->productEntityIdentifierField) {
            $this->productEntityIdentifierField = $this->getMetadataPool()
                                                       ->getMetadata(ProductInterface::class)
                                                       ->getIdentifierField();
        }

        return $this->productEntityIdentifierField;
    }

    /**
     * @return array|null
     * @throws FileSystemException
     */
    private function getTheNextBunch(): ?array
    {
        if ($this->baseHelper->checkModuleVersion('101.1.5', '', '<=', '<', 'Magento_CatalogImportExport')) {
            return $this->_dataSourceModel->getNextBunch();
        }

        return $this->_dataSourceModel->getNextUniqueBunch($this->getIds());
    }
}
