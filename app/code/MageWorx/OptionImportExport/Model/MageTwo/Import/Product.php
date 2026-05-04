<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionImportExport\Model\MageTwo\Import;

use Magento\CatalogImportExport\Model\Import\Product as ImportProduct;
use Magento\ImportExport\Model\Import as ImportModel;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Config as CatalogConfig;
use Magento\Catalog\Model\Product\Visibility;
use Magento\CatalogImportExport\Model\Import\Product\RowValidatorInterface as ValidatorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Framework\Model\ResourceModel\Db\ObjectRelationProcessor;
use Magento\Framework\Model\ResourceModel\Db\TransactionManagerInterface;
use Magento\Framework\Stdlib\DateTime;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingError;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use MageWorx\OptionImportExport\Model\MageTwo\ImportProductRegistry;
use MageWorx\OptionImportExport\Model\MageTwo\Import\Product\OptionFactory as MageWorxOptionEntityFactory;

class Product extends ImportProduct
{
    /**
     * @var string
     */
    protected $entityTypeCode;

    /**
     * @var MageWorxOptionEntityFactory
     */
    protected $mageworxOptionEntityFactory;

    /**
     * @var ImportProductRegistry
     */
    protected $importProductRegistry;

    /**
     * Catalog config.
     *
     * @var CatalogConfig
     */
    private $catalogConfig;

    /**
     * @var DateTimeFactory
     */
    private $dateTimeFactory;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @param MageWorxOptionEntityFactory $mageworxOptionEntityFactory
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\ImportExport\Helper\Data $importExportData
     * @param \Magento\ImportExport\Model\ResourceModel\Import\Data $importData
     * @param \Magento\Eav\Model\Config $config
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\ImportExport\Model\ResourceModel\Helper $resourceHelper
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration
     * @param \Magento\CatalogInventory\Model\Spi\StockStateProviderInterface $stockStateProvider
     * @param \Magento\Catalog\Helper\Data $catalogData
     * @param \Magento\ImportExport\Model\Import\Config $importConfig
     * @param \Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceModelFactory $resourceFactory
     * @param \Magento\CatalogImportExport\Model\Import\Product\OptionFactory $optionFactory
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $setColFactory
     * @param \Magento\CatalogImportExport\Model\Import\Product\Type\Factory $productTypeFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product\LinkFactory $linkFactory
     * @param \Magento\CatalogImportExport\Model\Import\Proxy\ProductFactory $proxyProdFactory
     * @param \Magento\CatalogImportExport\Model\Import\UploaderFactory $uploaderFactory
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\CatalogInventory\Model\ResourceModel\Stock\ItemFactory $stockResItemFac
     * @param DateTime\TimezoneInterface $localeDate
     * @param DateTime $dateTime
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry
     * @param \Magento\CatalogImportExport\Model\Import\Product\StoreResolver $storeResolver
     * @param \Magento\CatalogImportExport\Model\Import\Product\SkuProcessor $skuProcessor
     * @param \Magento\CatalogImportExport\Model\Import\Product\CategoryProcessor $categoryProcessor
     * @param \Magento\CatalogImportExport\Model\Import\Product\Validator $validator
     * @param ObjectRelationProcessor $objectRelationProcessor
     * @param TransactionManagerInterface $transactionManager
     * @param \Magento\CatalogImportExport\Model\Import\Product\TaxClassProcessor $taxClassProcessor
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Catalog\Model\Product\Url $productUrl
     * @param array $data
     * @param array $dateAttrCodes
     * @param CatalogConfig $catalogConfig
     * @param ImageTypeProcessor $imageTypeProcessor
     * @param MediaGalleryProcessor $mediaProcessor
     * @param StockItemImporterInterface|null $stockItemImporter
     * @param DateTimeFactory $dateTimeFactory
     * @param ProductRepositoryInterface|null $productRepository
     * @param ImportProductRegistry $importProductRegistry
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\FileSystemException
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function __construct(
        MageWorxOptionEntityFactory $mageworxOptionEntityFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\ImportExport\Helper\Data $importExportData,
        \Magento\ImportExport\Model\ResourceModel\Import\Data $importData,
        \Magento\Eav\Model\Config $config,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\ImportExport\Model\ResourceModel\Helper $resourceHelper,
        \Magento\Framework\Stdlib\StringUtils $string,
        ProcessingErrorAggregatorInterface $errorAggregator,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration,
        \Magento\CatalogInventory\Model\Spi\StockStateProviderInterface $stockStateProvider,
        \Magento\Catalog\Helper\Data $catalogData,
        \Magento\ImportExport\Model\Import\Config $importConfig,
        \Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceModelFactory $resourceFactory,
        \Magento\CatalogImportExport\Model\Import\Product\OptionFactory $optionFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $setColFactory,
        \Magento\CatalogImportExport\Model\Import\Product\Type\Factory $productTypeFactory,
        \Magento\Catalog\Model\ResourceModel\Product\LinkFactory $linkFactory,
        \Magento\CatalogImportExport\Model\Import\Proxy\ProductFactory $proxyProdFactory,
        \Magento\CatalogImportExport\Model\Import\UploaderFactory $uploaderFactory,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\CatalogInventory\Model\ResourceModel\Stock\ItemFactory $stockResItemFac,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        DateTime $dateTime,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry,
        \Magento\CatalogImportExport\Model\Import\Product\StoreResolver $storeResolver,
        \Magento\CatalogImportExport\Model\Import\Product\SkuProcessor $skuProcessor,
        \Magento\CatalogImportExport\Model\Import\Product\CategoryProcessor $categoryProcessor,
        \Magento\CatalogImportExport\Model\Import\Product\Validator $validator,
        ObjectRelationProcessor $objectRelationProcessor,
        TransactionManagerInterface $transactionManager,
        \Magento\CatalogImportExport\Model\Import\Product\TaxClassProcessor $taxClassProcessor,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Model\Product\Url $productUrl,
        ImportProductRegistry $importProductRegistry,
        CatalogConfig $catalogConfig,
        DateTimeFactory $dateTimeFactory,
        ProductRepositoryInterface $productRepository,
        array $data = [],
        array $dateAttrCodes = []
    ) {
        parent::__construct(
            $jsonHelper,
            $importExportData,
            $importData,
            $config,
            $resource,
            $resourceHelper,
            $string,
            $errorAggregator,
            $eventManager,
            $stockRegistry,
            $stockConfiguration,
            $stockStateProvider,
            $catalogData,
            $importConfig,
            $resourceFactory,
            $optionFactory,
            $setColFactory,
            $productTypeFactory,
            $linkFactory,
            $proxyProdFactory,
            $uploaderFactory,
            $filesystem,
            $stockResItemFac,
            $localeDate,
            $dateTime,
            $logger,
            $indexerRegistry,
            $storeResolver,
            $skuProcessor,
            $categoryProcessor,
            $validator,
            $objectRelationProcessor,
            $transactionManager,
            $taxClassProcessor,
            $scopeConfig,
            $productUrl,
            $data,
            array_merge($this->dateAttrCodes, $dateAttrCodes),
            $catalogConfig
        );
        $this->catalogConfig         = $catalogConfig;
        $this->dateTimeFactory       = $dateTimeFactory;
        $this->productRepository     = $productRepository;
        $this->importProductRegistry = $importProductRegistry;
        $data['option_entity']       = $mageworxOptionEntityFactory->create(
            ['data' => ['product_entity' => $this]]
        );
        $this->_optionEntity         = $data['option_entity'];
    }

    /**
     * Get Entity type code
     *
     * @return string
     */
    public function getEntityTypeCode()
    {
        $this->entityTypeCode = !$this->entityTypeCode ? 'catalog_product' : 'catalog_product_with_apo';

        return $this->entityTypeCode;
    }

    /**
     * Validate data row.
     *
     * @param array $rowData
     * @param int $rowNum
     * @return boolean
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @throws \Zend_Validate_Exception
     */
    public function validateRow(array $rowData, $rowNum)
    {
        if ($this->importProductRegistry->getIsImportValidation()) {
            if (isset($this->_validatedRows[$rowNum])) {
                return !$this->getErrorAggregator()->isRowInvalid($rowNum);
            }
        }
        $this->_validatedRows[$rowNum] = true;

        $rowScope = $this->getRowScope($rowData);
        $sku      = $rowData[self::COL_SKU];

        if (ImportModel::BEHAVIOR_DELETE === $this->getBehavior()) {
            return true;
        }

        $errorLevel = $this->getValidationErrorLevel($sku);

        if (!$this->validator->isValid($rowData)) {
            foreach ($this->validator->getMessages() as $message) {
                $this->skipRow($rowNum, $message, $errorLevel, $this->validator->getInvalidAttribute());
            }
        }

        if (null === $sku) {
            if ($this->getBehavior() === ImportModel::BEHAVIOR_ADD_UPDATE) {
                $this->skipRow($rowNum, ValidatorInterface::ERROR_SKU_IS_EMPTY, $errorLevel);
            }
        } elseif (false === $sku) {
            $this->skipRow($rowNum, ValidatorInterface::ERROR_ROW_IS_ORPHAN, $errorLevel);
        } elseif (self::SCOPE_STORE == $rowScope
            && !$this->storeResolver->getStoreCodeToId($rowData[self::COL_STORE])
        ) {
            $this->skipRow($rowNum, ValidatorInterface::ERROR_INVALID_STORE, $errorLevel);
        }

        if (!$this->importProductRegistry->getIsImportValidation() || self::SCOPE_DEFAULT === $rowScope) {
            $this->_processedEntitiesCount++;

            if ($this->isSkuExist($sku) && ImportModel::BEHAVIOR_REPLACE !== $this->getBehavior()) {
                if (isset($this->_productTypeModels[$this->getExistingSku($sku)['type_id']])) {
                    $this->skuProcessor->addNewSku(
                        $sku,
                        $this->prepareNewSkuData($sku)
                    );
                } else {
                    $this->skipRow($rowNum, ValidatorInterface::ERROR_TYPE_UNSUPPORTED, $errorLevel);
                }
            } else {
                if (!isset($rowData[self::COL_TYPE], $this->_productTypeModels[$rowData[self::COL_TYPE]])) {
                    $this->skipRow($rowNum, ValidatorInterface::ERROR_INVALID_TYPE, $errorLevel);
                } elseif (!isset($rowData[self::COL_ATTR_SET], $this->_attrSetNameToId[$rowData[self::COL_ATTR_SET]])) {
                    $this->skipRow($rowNum, ValidatorInterface::ERROR_INVALID_ATTR_SET, $errorLevel);
                } elseif ($this->skuProcessor->getNewSku($sku) === null) {
                    $this->skuProcessor->addNewSku(
                        $sku,
                        [
                            'row_id'        => null,
                            'entity_id'     => null,
                            'type_id'       => $rowData[self::COL_TYPE],
                            'attr_set_id'   => $this->_attrSetNameToId[$rowData[self::COL_ATTR_SET]],
                            'attr_set_code' => $rowData[self::COL_ATTR_SET],
                        ]
                    );
                }
            }

            if (!$this->getErrorAggregator()->isRowInvalid($rowNum)) {
                $newSku                      = $this->skuProcessor->getNewSku($sku);
                $rowData[self::COL_ATTR_SET] = $newSku['attr_set_code'];

                /** @var \Magento\CatalogImportExport\Model\Import\Product\Type\AbstractType $productTypeValidator */
                $productTypeValidator = $this->_productTypeModels[$newSku['type_id']];
                $productTypeValidator->isRowValid(
                    $rowData,
                    $rowNum,
                    !($this->isSkuExist($sku) && ImportModel::BEHAVIOR_REPLACE !== $this->getBehavior())
                );
            }
        }

        if ($this->isNeedToValidateUrlKey($rowData)) {
            $urlKey     = strtolower($this->getUrlKey($rowData));
            $storeCodes = empty($rowData[self::COL_STORE_VIEW_CODE])
                ? array_flip($this->storeResolver->getStoreCodeToId())
                : explode($this->getMultipleValueSeparator(), $rowData[self::COL_STORE_VIEW_CODE]);
            foreach ($storeCodes as $storeCode) {
                $storeId          = $this->storeResolver->getStoreCodeToId($storeCode);
                $productUrlSuffix = $this->getProductUrlSuffix($storeId);
                $urlPath          = $urlKey . $productUrlSuffix;
                if (empty($this->urlKeys[$storeId][$urlPath])
                    || ($this->urlKeys[$storeId][$urlPath] == $sku)
                ) {
                    $this->urlKeys[$storeId][$urlPath]    = $sku;
                    $this->rowNumbers[$storeId][$urlPath] = $rowNum;
                } else {
                    $message = sprintf(
                        $this->retrieveMessageTemplate(ValidatorInterface::ERROR_DUPLICATE_URL_KEY),
                        $urlKey,
                        $this->urlKeys[$storeId][$urlPath]
                    );
                    $this->addRowError(
                        ValidatorInterface::ERROR_DUPLICATE_URL_KEY,
                        $rowNum,
                        $rowData[self::COL_NAME],
                        $message,
                        $errorLevel
                    )
                         ->getErrorAggregator()
                         ->addRowToSkip($rowNum);
                }
            }
        }

        if (!empty($rowData['new_from_date']) && !empty($rowData['new_to_date'])) {
            $newFromTimestamp = strtotime($this->dateTime->formatDate($rowData['new_from_date'], false));
            $newToTimestamp   = strtotime($this->dateTime->formatDate($rowData['new_to_date'], false));
            if ($newFromTimestamp > $newToTimestamp) {
                $this->skipRow(
                    $rowNum,
                    'invalidNewToDateValue',
                    $errorLevel,
                    $rowData['new_to_date']
                );
            }
        }

        return !$this->getErrorAggregator()->isRowInvalid($rowNum);
    }

    /**
     * Check if product exists for specified SKU
     *
     * @param string $sku
     * @return bool
     */
    private function isSkuExist($sku)
    {
        if ($sku !== null) {
            $sku = strtolower($sku);
            return isset($this->_oldSku[$sku]);
        }
        return false;
    }

    /**
     * Add row as skipped
     *
     * @param int $rowNum
     * @param string $errorCode Error code or simply column name
     * @param string $errorLevel error level
     * @param string|null $colName optional column name
     * @return ImportProduct
     */
    private function skipRow(
        $rowNum,
        string $errorCode,
        string $errorLevel = ProcessingError::ERROR_LEVEL_NOT_CRITICAL,
        $colName = null
    ): ImportProduct {
        $this->addRowError($errorCode, $rowNum, $colName, null, $errorLevel);
        $this->getErrorAggregator()
             ->addRowToSkip($rowNum);
        return $this;
    }

    /**
     * Returns errorLevel for validation
     *
     * @param string $sku
     * @return string
     */
    private function getValidationErrorLevel($sku): string
    {
        return (!$this->isSkuExist($sku) && ImportModel::BEHAVIOR_REPLACE !== $this->getBehavior())
            ? ProcessingError::ERROR_LEVEL_CRITICAL
            : ProcessingError::ERROR_LEVEL_NOT_CRITICAL;
    }

    /**
     * Get existing product data for specified SKU
     *
     * @param string $sku
     * @return array
     */
    private function getExistingSku($sku)
    {
        return $this->_oldSku[strtolower($sku)];
    }

    /**
     * Prepare new SKU data
     *
     * @param string $sku
     * @return array
     */
    private function prepareNewSkuData($sku)
    {
        $data = [];
        foreach ($this->getExistingSku($sku) as $key => $value) {
            $data[$key] = $value;
        }

        $data['attr_set_code'] = $this->_attrSetIdToName[$this->getExistingSku($sku)['attr_set_id']];

        return $data;
    }

    /**
     * Check if need to validate url key.
     *
     * @param array $rowData
     * @return bool
     */
    private function isNeedToValidateUrlKey($rowData)
    {
        if (!empty($rowData[self::COL_SKU]) && empty($rowData[self::URL_KEY])
            && $this->getBehavior() === ImportModel::BEHAVIOR_APPEND
            && $this->isSkuExist($rowData[self::COL_SKU])) {
            return false;
        }

        return (!empty($rowData[self::URL_KEY]) || !empty($rowData[self::COL_NAME]))
            && (empty($rowData[self::COL_VISIBILITY])
                || $rowData[self::COL_VISIBILITY]
                !== (string)Visibility::getOptionArray()[Visibility::VISIBILITY_NOT_VISIBLE]);
    }

    /**
     * Obtain scope of the row from row data.
     *
     * @param array $rowData
     * @return int
     */
    public function getRowScope(array $rowData)
    {
        if (isset($rowData[self::COL_SKU])
            && strlen(trim($rowData[self::COL_SKU]))
            && empty($rowData[self::COL_STORE])
        ) {
            return self::SCOPE_DEFAULT;
        } elseif (empty($rowData[self::COL_STORE])) {
            return self::SCOPE_NULL;
        }
        return self::SCOPE_STORE;
    }
}
