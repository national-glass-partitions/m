<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionImportExport\Model\MageOne;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\IntegrationException;
use Magento\Framework\Filesystem;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Escaper;
use Magento\Framework\File\Csv as FileCsv;
use MageWorx\OptionBase\Model\Product\Attributes as ProductAttributes;
use MageWorx\OptionBase\Model\Product\Option\Attributes as OptionAttributes;
use MageWorx\OptionBase\Model\Product\Option\Value\Attributes as ValueAttributes;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionTemplates\Model\Group as GroupModel;
use MageWorx\OptionTemplates\Model\GroupFactory;
use MageWorx\OptionTemplates\Model\Group\OptionFactory as GroupOptionFactory;
use MageWorx\OptionImportExport\Helper\Data as Helper;
use MageWorx\OptionFeatures\Helper\Image as ImageHelper;
use Magento\Framework\Serialize\Serializer\Serialize as Serializer;
use MageWorx\OptionBase\Model\OptionHandler;
use MageWorx\OptionImportExport\Model\Config\Source\BeforeImportSystemStatus;
use MageWorx\OptionImportExport\Model\Config\Source\MigrationMode;

class ImportOptionsHandler
{
    const IMPORT_MODE_FULL           = 'full';
    const IMPORT_MODE_OPTIONS_ONLY   = 'options_only';
    const IMPORT_MODE_TEMPLATES_ONLY = 'templates_only';

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $mediaDirectory;

    /**
     * CSV Processor
     *
     * @var FileCsv
     */
    protected $csvProcessor;

    /**
     * @var Serializer
     */
    protected $serializer;

    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * @var ProductAttributes
     */
    protected $productAttributes;

    /**
     * @var OptionAttributes
     */
    protected $optionAttributes;

    /**
     * @var ValueAttributes
     */
    protected $valueAttributes;

    /**
     * @var BaseHelper
     */
    protected $baseHelper;

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * @var ImageHelper
     */
    protected $imageHelper;

    /**
     * @var GroupModel
     */
    protected $groupModel;

    /**
     * @var GroupFactory
     */
    protected $groupFactory;

    /**
     * @var GroupOptionFactory
     */
    protected $groupOptionFactory;

    /**
     * @var EventManager
     */
    protected $eventManager;

    /**
     * @var OptionHandler
     */
    protected $optionHandler;

    /**
     * List of fields that must be in option, except APO attributes
     *
     * @var string[]
     */
    protected $optionValidationRules = [
        'not-empty' => [
            '_custom_option_type'
        ],
        'required'  => [
            '_custom_option_title',
            '_custom_option_is_required',
            '_custom_option_in_group_id'
        ]
    ];

    /**
     * List of fields that must be in group option value, except APO attributes
     *
     * @var string[]
     */
    protected $valueValidationRules = [
        'not-empty' => [
        ],
        'required'  => [
            '_custom_option_row_title',
            '_custom_option_row_in_group_id'
        ]
    ];

    /**
     * @var array
     */
    protected $customerGroupMap = [];

    /**
     * @var array
     */
    protected $storeIdMap = [];

    /**
     * @var array
     */
    protected $customerEquivalentMap = [];

    /**
     * @var array
     */
    protected $storeEquivalentMap = [];

    /**
     * @var array
     */
    protected $missingImagesList = [];

    /**
     * @var string
     */
    protected $currentProductSku = '';

    /**
     * @var array
     */
    protected $storeIds = [];

    /**
     * @var array
     */
    protected $customerGroupIds = [];

    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var array
     */
    protected $keyIndexMap = [];

    /**
     * Default magento fields and APO product level fields
     *
     * @var array
     */
    protected $productLevelKeys = [
        'sku',
        '_absolute_price',
        '_absolute_weight',
        '_sku_policy'
    ];

    /**
     * Default magento option level fields and APO option level fields
     *
     * @var array
     */
    protected $optionLevelKeys = [
        '_custom_option_store',
        '_custom_option_type',
        '_custom_option_title',
        '_custom_option_is_required',
        '_custom_option_price',
        '_custom_option_sku',
        '_custom_option_max_characters',
        '_custom_option_sort_order',
        '_custom_option_file_extension',
        '_custom_option_image_size_x',
        '_custom_option_image_size_y',
        '_custom_option_template_id',
        '_custom_option_view_mode',
        '_custom_option_customoptions_is_onetime',
        '_custom_option_show_swatch_title',
        '_custom_option_image_path',
        '_custom_option_customer_groups',
        '_custom_option_store_views',
        '_custom_option_qnty_input',
        '_custom_option_in_group_id',
        '_custom_option_is_dependent',
        '_custom_option_div_class',
        '_custom_option_image_mode',
        '_custom_option_exclude_first_image',
        '_custom_option_description',
        '_custom_option_default_text',
        '_custom_option_sku_policy'
    ];

    /**
     * Default magento option level fields and APO option level fields with possibility to be changed on store view
     * level
     *
     * @var array
     */
    protected $optionLevelStoreViewKeys = [
        '_custom_option_title',
        '_custom_option_price',
        '_custom_option_view_mode',
        '_custom_option_description'
    ];

    /**
     *  Default magento value level fields and APO value level fields
     *
     * @var array
     */
    protected $valueLevelKeys = [
        '_custom_option_row_title',
        '_custom_option_row_price',
        '_custom_option_row_sku',
        '_custom_option_row_sort',
        '_custom_option_row_customoptions_qty',
        '_custom_option_row_customoptions_min_qty',
        '_custom_option_row_customoptions_max_qty',
        '_custom_option_row_image_data',
        '_custom_option_row_default',
        '_custom_option_row_in_group_id',
        '_custom_option_row_dependent_ids',
        '_custom_option_row_weight',
        '_custom_option_row_cost',
        '_custom_option_row_extra',
        '_custom_option_row_special_data',
        '_custom_option_row_tier_data',
        '_custom_option_row_description'
    ];

    /**
     *  Default magento value level fields and APO value level fields with possibility to be changed on store view level
     *
     * @var array
     */
    protected $valueLevelStoreViewKeys = [
        '_custom_option_row_title',
        '_custom_option_row_price',
        '_custom_option_row_special_data',
        '_custom_option_row_tier_data',
        '_custom_option_row_description'
    ];

    /**
     * @var array
     */
    protected $systemData = [];

    /**
     * @var array
     */
    protected $processedTemplateMap = [];

    /**
     * @var array
     */
    protected $templateIds = [];

    /**
     * @var array
     */
    protected $productSkuToGroupIdRelations = [];

    /**
     * @var string
     */
    protected $beforeImportSystemStatus = '';

    /**
     * @var string
     */
    protected $migrationMode = '';

    /**
     * @var string
     */
    protected $importMode = '';

    /**
     * ImportTemplateHandler constructor.
     *
     * @param Escaper $escaper
     * @param FileCsv $csvProcessor
     * @param ProductAttributes $productAttributes
     * @param OptionAttributes $optionAttributes
     * @param ValueAttributes $valueAttributes
     * @param BaseHelper $baseHelper
     * @param Helper $helper
     * @param ImageHelper $imageHelper
     * @param EventManager $eventManager
     * @param Filesystem $filesystem
     * @param ResourceConnection $resource
     * @param GroupFactory $groupFactory
     * @param GroupOptionFactory $groupOptionFactory
     * @param Serializer $serializer
     * @param OptionHandler $optionHandler
     * @param GroupModel $groupModel
     */
    public function __construct(
        Escaper $escaper,
        FileCsv $csvProcessor,
        ProductAttributes $productAttributes,
        OptionAttributes $optionAttributes,
        ValueAttributes $valueAttributes,
        BaseHelper $baseHelper,
        Helper $helper,
        ImageHelper $imageHelper,
        EventManager $eventManager,
        Filesystem $filesystem,
        ResourceConnection $resource,
        GroupFactory $groupFactory,
        Serializer $serializer,
        OptionHandler $optionHandler,
        GroupOptionFactory $groupOptionFactory,
        GroupModel $groupModel
    ) {
        $this->csvProcessor       = $csvProcessor;
        $this->escaper            = $escaper;
        $this->serializer         = $serializer;
        $this->productAttributes  = $productAttributes;
        $this->optionAttributes   = $optionAttributes;
        $this->valueAttributes    = $valueAttributes;
        $this->baseHelper         = $baseHelper;
        $this->helper             = $helper;
        $this->imageHelper        = $imageHelper;
        $this->groupFactory       = $groupFactory;
        $this->groupOptionFactory = $groupOptionFactory;
        $this->eventManager       = $eventManager;
        $this->resource           = $resource;
        $this->optionHandler      = $optionHandler;
        $this->groupModel         = $groupModel;
        $this->mediaDirectory     = $filesystem->getDirectoryWrite('media');
    }

    /**
     * Import group data from file
     *
     * @param array $file file info retrieved from $_FILES array
     * @param array $map
     * @param array $templateMap
     * @throws \Exception
     * @throws \Magento\Framework\Exception\IntegrationException
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws LocalizedException
     */
    public function importFromFile($file, $map, $templateMap = [])
    {
        if (!isset($file['tmp_name'])) {
            throw new LocalizedException(__('Invalid file upload attempt'));
        }
        $fileData = $this->csvProcessor->getData($file['tmp_name']);
        if (!is_array($fileData) || !$fileData) {
            throw new LocalizedException(
                __('Invalid file data')
            );
        }

        if (!is_array($fileData) || count($fileData) < 2 || empty($fileData[0]) || !is_array($fileData[0])) {
            throw new LocalizedException(
                __('Data for import not found')
            );
        }

        $this->collectCsvKeyIndexMap($fileData);
        $this->validateCsvFields();
        $data = $this->collectCsvData($fileData);

        $this->setMigrationMode($map);
        $this->checkExistingProducts($data);

        $this->validateData($data);

        $this->setEquivalentMaps($map);
        $this->validateSystemData($data, $map);

        $this->processTemplateMap($data, $templateMap);

        $preparedData = $this->prepareData($data);

        $productAttributesData = $this->collectProductAttributes($data);

        $this->resource->getConnection()->beginTransaction();
        try {
            $this->importData($preparedData, $productAttributesData);
        } catch (\Exception $e) {
            $this->resource->getConnection()->rollBack();
            throw $e;
        }
        $this->resource->getConnection()->commit();
        $this->clearTempVariables();
    }

    /**
     * Collect key-index pairs to parse product csv
     *
     * @param array $fileData
     * @return void
     */
    protected function collectCsvKeyIndexMap($fileData)
    {
        $this->keyIndexMap = array_flip($fileData[0]);
        foreach ($this->keyIndexMap as $key => $index) {
            if (!in_array($key, $this->productLevelKeys)
                && !in_array($key, $this->optionLevelKeys)
                && !in_array($key, $this->valueLevelKeys)
            ) {
                unset($this->keyIndexMap[$key]);
            }
        }
    }

    /**
     * Process template map
     *
     * @param array $data
     * @param array $templateMap
     */
    protected function processTemplateMap($data, $templateMap)
    {
        $this->processedTemplateMap = [];
        if (!$data || !$this->templateIds || !is_array($this->templateIds)) {
            return;
        }
        $this->groupModel->processTemplateMap($templateMap, $this->templateIds);

        foreach ($templateMap as $templateId => $templateMapData) {
            foreach ($templateMapData['options'] as $option) {
                $this->processedTemplateMap[$templateId]['group_id'] = $templateMapData['group_id'];
                if (!isset($option['group_option_id'])) {
                    continue;
                }
                $this->processedTemplateMap[$templateId]['options'][$option['option_id']] = $option['group_option_id'];
                if (!isset($option['values']) || !is_array($option['values'])) {
                    continue;
                }
                foreach ($option['values'] as $value) {
                    if (!isset($value['group_value_id'])) {
                        continue;
                    }
                    $this->processedTemplateMap[$templateId]['values'][$value['value_id']] = $value['group_value_id'];
                }
            }
        }
    }

    /**
     * Check existence of some necessary fields in product-csv
     *
     * @return void
     * @throws LocalizedException
     */
    protected function validateCsvFields()
    {
        $validationKeys = ['sku', '_custom_option_type', '_custom_option_title', '_custom_option_row_title'];
        foreach ($validationKeys as $validationKey) {
            if (!isset($this->keyIndexMap[$validationKey])) {
                throw new LocalizedException(
                    __("Invalid data for import, key '%1'", $validationKey)
                );
            }
        }
    }

    /**
     * Collect product's data with options
     * format: [productSku => [productDataArray]]
     *
     * @param array $fileData
     * @return array
     */
    protected function collectCsvData($fileData)
    {
        $isFirstRowPassed = false;
        $currentStoreCode = '0';
        $productSku       = '';
        $productData      = [];
        $optionData       = [];
        $valueData        = [];
        $product          = [];
        $option           = [];
        $value            = [];

        $currentIndex  = 0;
        $valueIndex    = 0;
        $maxValueIndex = 0;
        foreach ($fileData as $fileDataRow) {
            if ($isFirstRowPassed === false) {
                $isFirstRowPassed = true;
                continue;
            }

            if ($fileDataRow[$this->keyIndexMap['sku']] !== '') {
                $this->addNewOption($optionData, $value, $valueData, $option);
                if (!empty($optionData)) {
                    $product['options'] = $optionData;
                }
                if (!empty($product) && !empty($product['options'])) {
                    $productData[$productSku] = $product;
                }

                $maxValueIndex = 0;
                $currentIndex  = 0;
                $valueIndex    = 0;

                $productSku = $fileDataRow[$this->keyIndexMap['sku']];
                $product    = [];
                $optionData = [];
                $option     = [];
                $valueData  = [];
                $value      = [];
            }
            foreach ($this->productLevelKeys as $productLevelKey) {
                if ($fileDataRow[$this->keyIndexMap[$productLevelKey]] !== '') {
                    $product[$productLevelKey] = $fileDataRow[$this->keyIndexMap[$productLevelKey]];
                }
            }

            if ($fileDataRow[$this->keyIndexMap['_custom_option_store']] !== '') {
                if ($currentStoreCode === $fileDataRow[$this->keyIndexMap['_custom_option_store']]) {
                    $currentIndex++;
                } else {
                    $this->addNewValue($valueData, $value);
                    $value        = [];
                    $currentIndex = 0;
                }
                $currentStoreCode = $fileDataRow[$this->keyIndexMap['_custom_option_store']];
            } else {
                if ($valueIndex > $maxValueIndex) {
                    $maxValueIndex = $valueIndex;
                }
                $valueIndex++;
                $currentStoreCode = '0';
            }
            if ($fileDataRow[$this->keyIndexMap['_custom_option_title']] !== '' && $currentStoreCode === '0') {
                $maxValueIndex = 0;
                $this->addNewOption($optionData, $value, $valueData, $option);
                $option    = [];
                $valueData = [];
                $value     = [];
            }
            foreach ($this->optionLevelKeys as $optionLevelKey) {
                if ($fileDataRow[$this->keyIndexMap[$optionLevelKey]] === '') {
                    continue;
                }
                if (in_array($optionLevelKey, $this->optionLevelStoreViewKeys)) {
                    $option[$optionLevelKey][$currentStoreCode] = $fileDataRow[$this->keyIndexMap[$optionLevelKey]];
                } else {
                    $option[$optionLevelKey] = $fileDataRow[$this->keyIndexMap[$optionLevelKey]];
                }
            }

            if ($fileDataRow[$this->keyIndexMap['_custom_option_row_title']] !== '' && $currentStoreCode === '0') {
                $this->addNewValue($valueData, $value);
                $value = [];
            }
            foreach ($this->valueLevelKeys as $valueLevelKey) {
                if ($fileDataRow[$this->keyIndexMap[$valueLevelKey]] === '') {
                    continue;
                }
                if (in_array($valueLevelKey, $this->valueLevelStoreViewKeys)) {
                    if ($currentStoreCode === '0' || ($maxValueIndex < $currentIndex && $currentStoreCode !== '0')) {
                        $value[$valueLevelKey][$currentStoreCode] = $fileDataRow[$this->keyIndexMap[$valueLevelKey]];
                    } else {
                        $valueData[$currentIndex][$valueLevelKey][$currentStoreCode] =
                            $fileDataRow[$this->keyIndexMap[$valueLevelKey]];
                    }
                } else {
                    $value[$valueLevelKey] = $fileDataRow[$this->keyIndexMap[$valueLevelKey]];
                }
            }
        }

        $this->addNewOption($optionData, $value, $valueData, $option);
        if (!empty($optionData)) {
            $product['options'] = $optionData;
        }
        if (!empty($product) && !empty($product['options'])) {
            $productData[$productSku] = $product;
        }

        return $productData;
    }

    /**
     * Add new option to options array
     *
     * @param array $optionData
     * @param array $value
     * @param array $valueData
     * @param array $option
     * @return void
     */
    protected function addNewOption(&$optionData, $value, $valueData, $option)
    {
        if (!empty($value)) {
            $valueData[] = $value;
        }
        if (!empty($valueData)) {
            $option['values'] = $valueData;
        }
        if (!empty($option)) {
            $optionData[] = $option;
        }
    }

    /**
     * Add new value to values array
     *
     * @param array $valueData
     * @param array $value
     * @return void
     */
    protected function addNewValue(&$valueData, $value)
    {
        if (!empty($value)) {
            $valueData[] = $value;
        }
    }

    /**
     * @param array $map
     */
    protected function setMigrationMode($map)
    {
        if (!isset($map['mageworx_mage_one_migration_mode'])
            || $map['mageworx_mage_one_migration_mode'] === MigrationMode::MIGRATION_MODE_ADD_OPTIONS_TO_THE_END
        ) {
            $this->migrationMode = MigrationMode::MIGRATION_MODE_ADD_OPTIONS_TO_THE_END;
            return;
        }

        if ($map['mageworx_mage_one_migration_mode'] === MigrationMode::MIGRATION_MODE_DELETE_ALL_OPTIONS) {
            $this->migrationMode = MigrationMode::MIGRATION_MODE_DELETE_ALL_OPTIONS;
            return;
        }

        if ($map['mageworx_mage_one_migration_mode'] === MigrationMode::MIGRATION_MODE_DELETE_OPTIONS_ON_INTERSECTING_PRODUCTS) {
            $this->migrationMode = MigrationMode::MIGRATION_MODE_DELETE_OPTIONS_ON_INTERSECTING_PRODUCTS;
            return;
        }

        $this->migrationMode = '';
    }

    /**
     * Compare product's SKUs from M1 with existing products on M2
     *
     * @param array $data
     * @throws NotFoundException
     */
    protected function checkExistingProducts($data)
    {
        $isProductsExistInMageTwo = $this->isProductsExistInMageTwo($data);
        if (!$isProductsExistInMageTwo) {
            throw new NotFoundException(
                __("There are no matching items in the file. Product options import is canceled.")
            );
        }

        $isCustomOptionsExistInMageTwo = $this->isCustomOptionsExistInMageTwo();
        if (!$isCustomOptionsExistInMageTwo) {
            $this->beforeImportSystemStatus = BeforeImportSystemStatus::BEFORE_IMPORT_SYSTEM_STATUS_OPTIONS_FREE;
            return;
        }

        $intersectingProducts = $this->hasIntersectingProducts($data);
        if ($intersectingProducts) {
            $this->beforeImportSystemStatus = BeforeImportSystemStatus::BEFORE_IMPORT_SYSTEM_STATUS_INTERSECTION;
            return;
        }

        $this->beforeImportSystemStatus = BeforeImportSystemStatus::BEFORE_IMPORT_SYSTEM_STATUS_NO_INTERSECTION;
    }

    /**
     * Get before import system status to give opportunity to select import mode for administrator
     *
     * @return string
     */
    public function getBeforeImportSystemStatus(): string
    {
        return (string)$this->beforeImportSystemStatus;
    }

    /**
     * Validate data from M1 to M2
     *
     * @param array $data
     * @return array
     */
    protected function prepareData($data)
    {
        $preparedData = [];

        if (!is_array($data)) {
            return [];
        }

        $this->addEquivalentMap();

        $sortOrderCounter = 1;
        foreach ($data as $sku => $product) {
            foreach ($product['options'] as $option) {

                $optionData = [
                    'sort_order' => (string)$sortOrderCounter,
                    'record_id'  => $option['_custom_option_in_group_id']
                ];

                if (!empty($option['_custom_option_template_id'])
                    && !empty($this->processedTemplateMap)
                    && isset($this->processedTemplateMap[$option['_custom_option_template_id']])
                ) {
                    $inGroupId  = $option['_custom_option_in_group_id'] - $option['_custom_option_template_id'] * 65535;
                    $newGroupId = $this->processedTemplateMap[$option['_custom_option_template_id']]['group_id'];

                    $this->productSkuToGroupIdRelations[$sku][$newGroupId] = $newGroupId;

                    if (!empty($this->processedTemplateMap[$option['_custom_option_template_id']]['options'][$inGroupId])) {
                        $optionData['group_option_id'] = $this->processedTemplateMap[$option['_custom_option_template_id']]['options'][$inGroupId];
                    } else {
                        $optionData['group_option_id'] = null;
                    }
                }

                if ($this->isOptionTitleExistForAdminStore($option)) {
                    $optionData['title'] = $option['_custom_option_title']['0'];
                }

                if (!$this->isSelectableOption($option['_custom_option_type'])) {
                    if (!empty($option['_custom_option_price']) && isset($option['_custom_option_price']['0'])) {
                        if (substr($option['_custom_option_price']['0'], -1) === '%') {
                            $optionData['price_type'] = 'percent';
                        } else {
                            $optionData['price_type'] = 'fixed';
                        }
                        $optionData['price'] = (float)rtrim($option['_custom_option_price']['0'], '%');
                    } else {
                        $option['_custom_option_price']['0'] = 0;
                        $optionData['price']                 = 0;
                        $optionData['price_type']            = 'fixed';
                    }
                }

                $standardOptionFields = [
                    '_custom_option_type'           => 'type',
                    '_custom_option_is_required'    => 'is_require',
                    '_custom_option_sku'            => 'sku',
                    '_custom_option_max_characters' => 'max_characters',
                    '_custom_option_file_extension' => 'file_extension',
                    '_custom_option_image_size_x'   => 'image_size_x',
                    '_custom_option_image_size_y'   => 'image_size_y'
                ];
                foreach ($standardOptionFields as $field => $mageTwoEquivalent) {
                    if (isset($option[$field])) {
                        $optionData[$mageTwoEquivalent] = $option[$field];
                    }
                }

                $this->prepareImages($option);

                $optionAttributes = $this->optionAttributes->getData();
                foreach ($optionAttributes as $optionAttribute) {
                    /** @var \MageWorx\OptionBase\Api\ImportInterface $optionAttribute */
                    $optionAttribute->prepareOptionsMageOne($this->systemData, $product, $option, $optionData);
                }

                $this->prepareOptionValues($optionData, $product, $option);

                $preparedData[$sku][] = $optionData;
                $sortOrderCounter++;
            }
        }

        return $preparedData;
    }

    /**
     * Add equivalent map to use in option preparation method
     *
     * @return void
     */
    protected function addEquivalentMap()
    {
        $this->systemData['map']['store']          = $this->storeEquivalentMap ?: [];
        $this->systemData['map']['store'][0]       = 0;
        $this->systemData['map']['customer_group'] = $this->customerEquivalentMap ?: [];
    }

    /**
     * Prepare value's data
     *
     * @param array $optionData
     * @param array $product
     * @param array $option
     */
    protected function prepareOptionValues(&$optionData, $product, $option)
    {
        if (!isset($option['values']) || !is_array($option['values'])) {
            return;
        }
        $sortOrderCounter = 1;
        foreach ($option['values'] as $value) {
            $valueData = [
                'sort_order' => (string)$sortOrderCounter
            ];

            $valueData['record_id'] = $value['_custom_option_row_in_group_id'];

            if (!empty($option['_custom_option_template_id']) && !empty($this->processedTemplateMap)) {
                $inGroupId = $value['_custom_option_row_in_group_id'] - $option['_custom_option_template_id'] * 65535;

                if (!empty($this->processedTemplateMap[$option['_custom_option_template_id']]['values'][$inGroupId])) {
                    $valueData['group_option_value_id'] = $this->processedTemplateMap[$option['_custom_option_template_id']]['values'][$inGroupId];
                } else {
                    $valueData['group_option_value_id'] = null;
                }
            }

            if ($this->isValueTitleExistForAdminStore($value)) {
                $valueData['title'] = $value['_custom_option_row_title']['0'];
            }

            if ($this->isSelectableOption($option['_custom_option_type'])) {
                if (!empty($value['_custom_option_row_price']) && isset($value['_custom_option_row_price']['0'])) {
                    if (substr($value['_custom_option_row_price']['0'], -1) === '%') {
                        $valueData['price_type'] = 'percent';
                    } else {
                        $valueData['price_type'] = 'fixed';
                    }
                    $valueData['price'] = (float)rtrim($value['_custom_option_row_price']['0'], '%');
                } else {
                    $value['_custom_option_row_price']['0'] = 0;
                    $valueData['price']                     = 0;
                    $valueData['price_type']                = 'fixed';
                }
            }

            if (isset($value['_custom_option_row_sku'])) {
                $valueData['sku'] = $value['_custom_option_row_sku'];
            }

            $valueAttributes = $this->valueAttributes->getData();
            foreach ($valueAttributes as $valueAttribute) {
                /** @var \MageWorx\OptionBase\Api\ImportInterface $valueAttribute */
                $valueAttribute->prepareOptionsMageOne(
                    $this->systemData,
                    $product,
                    $option,
                    $optionData,
                    $value,
                    $valueData
                );
            }
            $optionData['values'][] = $valueData;
            $sortOrderCounter++;
        }
    }

    /**
     * Is Magento1 option type is selectable option type
     *
     * @param string $optionType
     * @return bool
     */
    protected function isSelectableOption($optionType)
    {
        $types = [
            'drop_down',
            'radio',
            'multiple',
            'checkbox',
            'hidden',
            'swatch',
            'multiswatch'
        ];
        return in_array($optionType, $types);
    }

    /**
     * Collect product attributes for mass import
     *
     * @param array $data
     * @return array
     */
    protected function collectProductAttributes($data)
    {
        if (!is_array($data)) {
            return [];
        }

        $productAttributesData = [];
        $productAttributes     = $this->productAttributes->getData();
        foreach ($productAttributes as $productAttribute) {
            /** @var \MageWorx\OptionBase\Api\ProductAttributeInterface $productAttribute */
            $productAttribute->prepareOptionsMageOne($productAttributesData, $data);
        }
        return $productAttributesData;
    }

    /**
     * Is option title exist for admin store
     *
     * @param array $option
     * @return bool
     */
    protected function isOptionTitleExistForAdminStore($option)
    {
        return !empty($option['_custom_option_title'])
            && isset($option['_custom_option_title']['0'])
            && $option['_custom_option_title']['0'] !== '';
    }

    /**
     * Is value title exist for admin store
     *
     * @param array $value
     * @return bool
     */
    protected function isValueTitleExistForAdminStore($value)
    {
        return !empty($value['_custom_option_row_title'])
            && isset($value['_custom_option_row_title']['0'])
            && $value['_custom_option_row_title']['0'] !== '';
    }

    /**
     * Check if products exist in magento
     *
     * @param array $data
     * @return bool
     */
    protected function isProductsExistInMageTwo($data)
    {
        return $this->optionHandler->isProductsExist($data);
    }

    /**
     * Check if custom options exist in magento
     *
     * @return bool
     */
    protected function isCustomOptionsExistInMageTwo()
    {
        return $this->optionHandler->isCustomOptionsExist();
    }

    /**
     * Check if custom options in magento intersects with csv
     *
     * @param array $data
     * @return bool
     */
    protected function hasIntersectingProducts($data)
    {
        return $this->optionHandler->hasIntersectingProducts($data);
    }

    /**
     * Validate data integrity
     *
     * @param array $data
     * @throws LocalizedException
     */
    protected function validateData($data)
    {
        foreach ($data as $datum) {
            $this->validateProductData($datum);
        }
    }

    /**
     * Validate product integrity
     *
     * @param array $data
     * @throws LocalizedException
     */
    protected function validateProductData($data)
    {
        $this->currentProductSku = $data['sku'];
        $this->validateOptions($data);
    }

    /**
     * Validate options integrity
     *
     * @param array $data
     * @throws LocalizedException
     */
    protected function validateOptions($data)
    {
        if (empty($data['options']) || !is_array($data['options'])) {
            return;
        }


        foreach ($data['options'] as $optionData) {
            if (isset($optionData['_custom_option_template_id'])) {
                $this->templateIds[$optionData['_custom_option_template_id']] = $optionData['_custom_option_template_id'];
            }
            $this->validateOptionDefaults($optionData);
            $this->validateOptionAttributes($optionData);
            $this->validateValues($optionData, $data['sku']);
        }
    }

    /**
     * Validate options default fields
     *
     * @param array $data
     * @throws LocalizedException
     */
    protected function validateOptionDefaults($data)
    {
        foreach ($this->optionValidationRules['not-empty'] as $field) {
            if (!empty($data[$field])) {
                continue;
            }
            throw new LocalizedException(
                __("'%1' option's field '%2' not found or empty", $this->currentProductSku, $field)
            );
        }
        foreach ($this->optionValidationRules['required'] as $field) {
            if (isset($data[$field])) {
                continue;
            }
            throw new LocalizedException(
                __("'%1' option's field '%2' not found", $this->currentProductSku, $field)
            );
        }
    }

    /**
     * Validate APO option attributes
     *
     * @param array $data
     * @throws LocalizedException
     */
    protected function validateOptionAttributes($data)
    {
        $optionAttributes = $this->optionAttributes->getData();
        foreach ($optionAttributes as $optionAttribute) {
            /** @var \MageWorx\OptionBase\Api\ImportInterface $optionAttribute */
            $optionAttribute->validateOptionsMageOne($data);
        }
    }

    /**
     * @param $data
     * @param $validationProductSku
     * @throws LocalizedException
     */
    protected function validateValues($data, $validationProductSku)
    {
        if ((!isset($data['values']) || !is_array($data['values']))) {
            if ($this->isSelectableOption($data['_custom_option_type'])) {
                throw new LocalizedException(
                    __('Selectable option "%1" doesn\'t have values in product SKU= "'. $validationProductSku .'"',
                        $data['_custom_option_title']
                    )
                );
            }
            return;
        }

        foreach ($data['values'] as $valueData) {
            $this->validateValueDefaults($valueData);
            $this->validateValueAttributes($valueData);
        }
    }

    /**
     * Validate group option value's default fields
     *
     * @param array $data
     * @throws LocalizedException
     */
    protected function validateValueDefaults($data)
    {
        foreach ($this->valueValidationRules['not-empty'] as $field) {
            if (!empty($data[$field])) {
                continue;
            }
            throw new LocalizedException(
                __("'%1' option value's field '%2' not found or empty", $this->currentProductSku, $field)
            );
        }
        foreach ($this->valueValidationRules['required'] as $field) {
            if (isset($data[$field])) {
                continue;
            }
            throw new LocalizedException(
                __("'%1' option value's field '%2' not found", $this->currentProductSku, $field)
            );
        }
    }

    /**
     * Validate group's APO option value attributes
     *
     * @param array $data
     * @throws LocalizedException
     */
    protected function validateValueAttributes($data)
    {
        $valueAttributes = $this->valueAttributes->getData();
        foreach ($valueAttributes as $valueAttribute) {
            /** @var \MageWorx\OptionBase\Api\ImportInterface $valueAttribute */
            $valueAttribute->validateOptionsMageOne($data);
        }
    }

    /**
     * @param array $map
     * @return void
     */
    protected function setEquivalentMaps($map)
    {
        if (!empty($map['mageworx_optiontemplates_import_from_customer_groups'])) {
            $this->customerEquivalentMap          = $map['mageworx_optiontemplates_import_from_customer_groups'];
            $this->customerEquivalentMap[BaseHelper::ALL_CUSTOMER_GROUP_ID] = BaseHelper::ALL_CUSTOMER_GROUP_ID;
        }
        if (!empty($map['mageworx_optiontemplates_import_from_stores'])) {
            $this->storeEquivalentMap = $map['mageworx_optiontemplates_import_from_stores'];
        }
    }

    /**
     * Validate customer groups and store IDs data, because they may be different for M1 and M2
     *
     * @param array $map
     * @param array $data
     * @throws IntegrationException
     */
    protected function validateSystemData($data, $map)
    {
        $this->systemData = [];
        foreach ($data as $product) {
            $this->collectSystemData($product);
        }

        if ($this->isSystemDataEquivalentMapNeeded()) {
            $this->storeIds         = $this->storeIdMap;
            $this->customerGroupIds = $this->customerGroupMap;
            throw new IntegrationException(
                __("Please, link system specific data for Magento/Magento 2 and upload the file once again.")
            );
        }
        if (!$this->isSetMigrationMode($map)) {
            throw new IntegrationException(
                __("Please, select mode for existing Magento options.")
            );
        }
    }

    /**
     * Check of migration mode is set
     *
     * @param array $map
     * @return bool
     */
    protected function isSetMigrationMode($map)
    {
        if (!empty($map['mageworx_mage_one_migration_mode'])) {
            return true;
        }
        return false;
    }

    /**
     * Check if it is needed to set system data equivalent map (M1 to M2)
     *
     * @return bool
     */
    protected function isSystemDataEquivalentMapNeeded()
    {
        return $this->hasMageOneSystemData() && !$this->isSystemDataEquivalentMapExist();
    }

    /**
     * Check if equivalent map has duplicates of selected values
     *
     * @return bool
     */
    protected function hasSystemDataEquivalentMapDuplicates()
    {
        if ($this->hasMageOneSystemData() && !$this->isSystemDataEquivalentMapExist()) {
            return false;
        }

        if (!empty($this->customerEquivalentMap && is_array($this->customerEquivalentMap))) {
            $customerEqMapWithoutIgnore = [];
            foreach ($this->customerEquivalentMap as $key => $value) {
                if ($value !== '') {
                    $customerEqMapWithoutIgnore[$key] = $value;
                }
            }
            if (count($customerEqMapWithoutIgnore) !== count(array_unique($customerEqMapWithoutIgnore))) {
                return true;
            }
        }

        if (!empty($this->storeEquivalentMap && is_array($this->storeEquivalentMap))) {
            $storeEqMapWithoutIgnore = [];
            foreach ($this->storeEquivalentMap as $key => $value) {
                if ($value !== '') {
                    $storeEqMapWithoutIgnore[$key] = $value;
                }
            }
            if (count($storeEqMapWithoutIgnore) !== count(array_unique($storeEqMapWithoutIgnore))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if M1 file has any data about customer groups and stores
     *
     * @return bool
     */
    protected function hasMageOneSystemData()
    {
        return !empty($this->customerGroupMap) || !empty($this->storeIdMap);
    }

    /**
     * Check if customer groups and stores equivalent map exists
     *
     * @return bool
     */
    protected function isSystemDataEquivalentMapExist()
    {
        return !empty($this->customerEquivalentMap) || !empty($this->storeEquivalentMap);
    }

    /**
     * Collect system data from options (customer group IDs, store IDs) to map
     *
     * @param array $product
     */
    protected function collectSystemData($product)
    {
        if (!isset($product['options']) || !is_array($product['options'])) {
            return;
        }

        $options          = $product['options'];
        $optionAttributes = $this->optionAttributes->getData();
        $valueAttributes  = $this->valueAttributes->getData();

        foreach ($options as $optionData) {
            foreach ($optionAttributes as $optionAttribute) {
                /** @var \MageWorx\OptionBase\Api\ImportInterface $optionAttribute */
                $optionAttribute->collectOptionsSystemDataMageOne($this->systemData, $product, $optionData);
            }

            if ((!isset($optionData['values']) || !is_array($optionData['values']))) {
                continue;
            }

            foreach ($optionData['values'] as $valueData) {
                foreach ($valueAttributes as $valueAttribute) {
                    /** @var \MageWorx\OptionBase\Api\ImportInterface $valueAttribute */
                    $valueAttribute->collectOptionsSystemDataMageOne(
                        $this->systemData,
                        $product,
                        $optionData,
                        $valueData
                    );
                }
            }
        }

        if (!empty($this->systemData['store']) && is_array($this->systemData['store'])) {
            if (isset($this->systemData['store']) && isset($this->systemData['store']['0'])) {
                unset($this->systemData['store']['0']);
            }
            $this->storeIdMap += $this->systemData['store'];
        }

        if (!empty($this->systemData['customer_group']) && is_array($this->systemData['customer_group'])) {
            $this->customerGroupMap += $this->systemData['customer_group'];
        }
    }

    /**
     * Import data
     *
     * @param array $data
     * @param array $productAttributesData
     */
    protected function importData($data, $productAttributesData)
    {
        $this->optionHandler->addProductOptions(
            $data,
            $productAttributesData,
            $this->productSkuToGroupIdRelations,
            $this->migrationMode
        );
    }

    /**
     * Prepare images according to M2 requirements
     * Copy image files to M2 media APO directory
     *  
     * @param array $option
     * @throws FileSystemException
     * @return void
     */
    protected function prepareImages(&$option)
    {
        if (!isset($option['values']) || !is_array($option['values'])) {
            return;
        }

        foreach ($option['values'] as &$value) {
            if (!isset($value['_custom_option_row_image_data'])) {
                continue;
            }
            $value['_custom_option_row_image_data'] = str_replace('\\', '/', $value['_custom_option_row_image_data']);
            $images = explode('|', $value['_custom_option_row_image_data']);
            foreach ($images as $image) {
                list($imageFile, $sortOrder, $source) = explode(':', $image);

                if ($source == 2) {
                    $color = substr($imageFile, 1);
                    $this->imageHelper->createColorFile($color);
                    continue;
                }

                $defaultMageOneDirectoryPath = 'mageworx/customoptions/';

                $sourcePath = $defaultMageOneDirectoryPath . $imageFile;

                $filePathArray = explode('/', $imageFile);
                if (!$filePathArray || !is_array($filePathArray)) {
                    continue;
                }
                $fileName = end($filePathArray);

                $filePath = '/'
                    . strtolower(substr($fileName, 0, 1))
                    . '/'
                    . strtolower(substr($fileName, 1, 1))
                    . '/'
                    . $fileName;

                $destinationPath = 'mageworx/optionfeatures/product/option/value' . $filePath;
                try {
                    $this->mediaDirectory->copyFile($sourcePath, $destinationPath);
                } catch (FileSystemException $e) {
                    if ($this->helper->isIgnoreMissingImages()) {
                        $this->missingImagesList[] = $sourcePath;
                        continue;
                    } else {
                        $this->storeIds         = $this->storeIdMap;
                        $this->customerGroupIds = $this->customerGroupMap;
                        throw new FileSystemException(__("The '%1' file doesn't exist.", $sourcePath));
                    }
                }
            }
        }
    }

    /**
     * Return missing images list (exist in database, missing in filesystem)
     *
     * @return array
     */
    public function getMissingImagesList()
    {
        return $this->missingImagesList ?: [];
    }

    /**
     * @return array
     */
    public function getCustomerGroupIds()
    {
        return $this->customerGroupIds;
    }

    /**
     * @return array
     */
    public function getStoreIds()
    {
        return $this->storeIds;
    }

    /**
     * @return void
     */
    protected function clearTempVariables()
    {
        $this->storeIds                 = [];
        $this->customerGroupIds         = [];
        $this->beforeImportSystemStatus = '';
    }
}
