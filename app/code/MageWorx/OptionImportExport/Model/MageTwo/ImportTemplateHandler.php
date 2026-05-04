<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionImportExport\Model\MageTwo;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\IntegrationException;
use Magento\Framework\Filesystem;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\App\ResourceConnection;
use MageWorx\OptionBase\Model\Product\Attributes as ProductAttributes;
use MageWorx\OptionBase\Model\Product\Option\Attributes as OptionAttributes;
use MageWorx\OptionBase\Model\Product\Option\Value\Attributes as ValueAttributes;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionTemplates\Model\Group;
use MageWorx\OptionTemplates\Model\GroupFactory;
use MageWorx\OptionTemplates\Model\Group\OptionFactory as GroupOptionFactory;
use MageWorx\OptionImportExport\Helper\Data as Helper;
use MageWorx\OptionFeatures\Helper\Image as ImageHelper;
use MageWorx\OptionTemplates\Model\OptionSaver;
use MageWorx\OptionTemplates\Model\ResourceModel\Product as ProductResourceModel;
use MageWorx\OptionLink\Model\OptionValueSkuVlidator as OptionValueSkuValidator;

class ImportTemplateHandler
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $mediaDirectory;

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
     * @var ProductResourceModel
     */
    protected $productResourceModel;

    /**
     * @var OptionSaver
     */
    protected $optionSaver;

    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * List of fields that must be validated in group, except APO attributes
     *
     * @var string[]
     */
    protected $groupValidationRules = [
        'not-empty' => [
            'title',
            'group_id'
        ],
        'required'  => [
            'is_active'
        ]
    ];

    /**
     * List of fields that must be in group option, except APO attributes
     *
     * @var string[]
     */
    protected $optionValidationRules = [
        'not-empty' => [
            'option_id',
            'type'
        ],
        'required'  => [
            'title',
            'is_require',
            'sort_order',
        ],
        'optional'  => [
            'price',
            'price_type',
            'sku',
            'max_characters',
            'file_extension',
            'image_size_x',
            'image_size_y'
        ]
    ];

    /**
     * List of fields that must be in group option value, except APO attributes
     *
     * @var string[]
     */
    protected $valueValidationRules = [
        'not-empty' => [
            'option_type_id',
            'price_type',
        ],
        'required'  => [
            'title',
            'price',
            'sort_order'
        ]
    ];

    /**
     * Option to value map from M1
     *
     * @var array
     */
    protected $optionValueMap = [];

    /**
     * @var int
     */
    protected $currentMagentoGroupId;

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
     * @var array
     */
    protected $storeOptionDescriptions = [];

    /**
     * @var array
     */
    protected $storeValueDescriptions = [];

    /**
     * @var string
     */
    protected $currentTemplateName = '';

    /**
     * @var array
     */
    protected $storeIds = [];

    /**
     * @var array
     */
    protected $customerGroupIds = [];

    /**
     * @var array
     */
    protected $assignedProducts = [];

    /**
     * @var array
     */
    protected $missingSkus = [];

    /**
     * @var bool
     */
    protected $isPermissionToApplyTemplatesCollected = false;

    /**
     * @var bool
     */
    protected $canSkipTemplatesApplying = false;

    /**
     * @var OptionValueSkuValidator
     */
    protected $optionValueSkuValidator;

    /**
     * ImportTemplateHandler constructor.
     *
     * @param ProductAttributes $productAttributes
     * @param OptionAttributes $optionAttributes
     * @param ValueAttributes $valueAttributes
     * @param BaseHelper $baseHelper
     * @param Helper $helper
     * @param ImageHelper $imageHelper
     * @param EventManager $eventManager
     * @param Filesystem $filesystem
     * @param GroupFactory $groupFactory
     * @param GroupOptionFactory $groupOptionFactory
     * @param ProductResourceModel $productResourceModel
     * @param ResourceConnection $resource
     * @param OptionSaver $optionSaver
     */
    public function __construct(
        ProductAttributes $productAttributes,
        OptionAttributes $optionAttributes,
        ValueAttributes $valueAttributes,
        BaseHelper $baseHelper,
        Helper $helper,
        ImageHelper $imageHelper,
        EventManager $eventManager,
        Filesystem $filesystem,
        GroupFactory $groupFactory,
        GroupOptionFactory $groupOptionFactory,
        ResourceConnection $resource,
        OptionSaver $optionSaver,
        ProductResourceModel $productResourceModel,
        OptionValueSkuValidator $optionValueSkuValidator
    ) {
        $this->productAttributes      = $productAttributes;
        $this->optionAttributes       = $optionAttributes;
        $this->valueAttributes        = $valueAttributes;
        $this->baseHelper             = $baseHelper;
        $this->helper                 = $helper;
        $this->imageHelper            = $imageHelper;
        $this->groupFactory           = $groupFactory;
        $this->groupOptionFactory     = $groupOptionFactory;
        $this->eventManager           = $eventManager;
        $this->productResourceModel   = $productResourceModel;
        $this->optionSaver            = $optionSaver;
        $this->resource               = $resource;
        $this->optionValueSkuValidator = $optionValueSkuValidator;
        $this->mediaDirectory         = $filesystem->getDirectoryWrite('media');
    }

    /**
     * Import group data from file
     *
     * @param array $file file info retrieved from $_FILES array
     * @param array $map
     * @throws \Exception
     * @throws \Magento\Framework\Exception\IntegrationException
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws LocalizedException
     */
    public function importFromFile($file, $map)
    {
        if (!isset($file['tmp_name']) || !file_exists($file['tmp_name'])) {
            throw new LocalizedException(__('Invalid file upload attempt'));
        }

        $data = $this->baseHelper->jsonDecode(file_get_contents($file['tmp_name']));

        if (!is_array($data) || !$data) {
            throw new LocalizedException(
                __('Invalid file data')
            );
        }

        $this->setTemplateApplyMode($map);
        $this->validateData($data);
        $this->setEquivalentMaps($map);
        $this->validateSystemData($data);

        if ($this->shouldShowTemplateApplyingMessage()) {
            throw new \Magento\Framework\Exception\InputException();
        }

        $this->resource->getConnection()->beginTransaction();
        try {
            $this->importData($data);
        } catch (\Exception $e) {
            $this->resource->getConnection()->rollBack();
            throw $e;
        }
        $this->resource->getConnection()->commit();
        $this->clearTempVariables();
    }

    /**
     * @return void
     */
    protected function clearTempVariables()
    {
        $this->storeIds         = [];
        $this->customerGroupIds = [];
        $this->missingSkus      = [];
        $this->assignedProducts = [];
    }

    /**
     * @param array $map
     * @return void
     */
    protected function setEquivalentMaps($map)
    {
        if (!empty($map['mageworx_optiontemplates_import_from_customer_groups'])) {
            $this->customerEquivalentMap = $map['mageworx_optiontemplates_import_from_customer_groups'];
        }
        $this->customerEquivalentMap[BaseHelper::ALL_CUSTOMER_GROUP_ID] = BaseHelper::ALL_CUSTOMER_GROUP_ID;

        if (!empty($map['mageworx_optiontemplates_import_from_stores'])) {
            $this->storeEquivalentMap = $map['mageworx_optiontemplates_import_from_stores'];
        }
    }

    /**
     * @return bool
     */
    public function shouldShowTemplateApplyingMessage()
    {
        return $this->assignedProducts && !$this->isPermissionToApplyTemplatesCollected;
    }

    /**
     * Validate data integrity
     *
     * @param array $map
     * @throws LocalizedException
     */
    protected function setTemplateApplyMode($map)
    {
        if (!isset($map['mageworx_optiontemplates_import_is_need_to_apply_templates'])) {
            return;
        }
        $this->isPermissionToApplyTemplatesCollected = true;
        $this->canSkipTemplatesApplying              = !$map['mageworx_optiontemplates_import_is_need_to_apply_templates'];
    }

    /**
     * Apply templates if needed
     *
     * @param Group $group
     * @return void
     */
    protected function applyTemplateToProducts($group)
    {
        if ($this->canSkipTemplatesApplying
            || empty($this->assignedProducts[$this->currentMagentoGroupId])
            || !is_array($this->assignedProducts[$this->currentMagentoGroupId])
        ) {
            return;
        }
        $currentTemplateAssignedProducts = $this->assignedProducts[$this->currentMagentoGroupId];
        $group->setProductsIds(array_values($currentTemplateAssignedProducts));
        $group->setProductRelation();
        $this->optionSaver->saveProductOptions($group, [], OptionSaver::SAVE_MODE_UPDATE);
    }

    /**
     * Validate data integrity
     *
     * @param array $data
     * @throws LocalizedException
     */
    protected function validateData($data)
    {
        foreach ($data as $dataItem) {
            if (empty($dataItem) || !is_array($dataItem)) {
                throw new LocalizedException(
                    __('Data for option template not found')
                );
            }
            $this->validateGroup($dataItem);
            $this->validateAssignedProductSkus($dataItem);
        }
    }

    /**
     * Validate group integrity
     *
     * @param array $data
     * @throws LocalizedException
     */
    protected function validateGroup($data)
    {
        $this->validateGroupDefaults($data);
        $this->currentTemplateName   = $data['title'];
        $this->currentMagentoGroupId = $data['group_id'];
        $this->validateGroupAttributes($data);
        $this->validateOptions($data);
    }

    /**
     * Validate presence of products assigned to imported template
     *
     * @param array $data
     * @throws LocalizedException
     */
    protected function validateAssignedProductSkus($data)
    {
        if (empty($data['assigned_product_skus']) || !is_array($data['assigned_product_skus'])) {
            return;
        }

        $assignedProductSkus = $data['assigned_product_skus'];
        $productSkus         = array_values($assignedProductSkus);

        $existProducts = $this->productResourceModel->getExistProducts($productSkus, $this->baseHelper->getLinkField());
        $missingSkus   = array_diff($productSkus, array_keys($existProducts));

        $this->missingSkus = $missingSkus;

        if (!$existProducts) {
            return;
        }
        $this->assignedProducts[$this->currentMagentoGroupId] = $existProducts;
    }

    /**
     * Validate group default fields
     *
     * @param array $data
     * @throws LocalizedException
     */
    protected function validateGroupDefaults($data)
    {
        foreach ($this->groupValidationRules['not-empty'] as $field) {
            if (!empty($data[$field])) {
                continue;
            }
            throw new LocalizedException(
                __("Template's '%1' field '%2' not found or empty", $this->currentTemplateName, $field)
            );
        }
        foreach ($this->groupValidationRules['required'] as $field) {
            if (isset($data[$field])) {
                continue;
            }
            throw new LocalizedException(
                __("Template's '%1 field '%2' not found", $this->currentTemplateName, $field)
            );
        }
    }

    /**
     * Validate group APO attributes
     *
     * @param array $data
     * @throws LocalizedException
     */
    protected function validateGroupAttributes($data)
    {
        $productAttributes = $this->productAttributes->getData();
        foreach ($productAttributes as $productAttribute) {
            /** @var \MageWorx\OptionBase\Api\ProductAttributeInterface $productAttribute */
            $productAttribute->validateTemplateImportMageTwo($data);
        }
    }

    /**
     * Validate group options integrity
     *
     * @param array $data
     * @throws LocalizedException
     */
    protected function validateOptions($data)
    {
        if (!isset($data['options']) || !is_array($data['options'])) {
            return;
        }

        $options = $data['options'];
        foreach ($options as $optionData) {
            $this->validateOptionDefaults($optionData);
            $this->validateOptionAttributes($optionData);
            $this->validateValues($optionData);
        }
    }

    /**
     * Validate group options default fields
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
                __("'%1' option's field '%2' not found or empty", $this->currentTemplateName, $field)
            );
        }
        foreach ($this->optionValidationRules['required'] as $field) {
            if (isset($data[$field])) {
                continue;
            }
            throw new LocalizedException(
                __("'%1' option's field '%2' not found", $this->currentTemplateName, $field)
            );
        }
    }

    /**
     * Validate group's APO option attributes
     *
     * @param array $data
     * @throws LocalizedException
     */
    protected function validateOptionAttributes($data)
    {
        $optionAttributes = $this->optionAttributes->getData();
        foreach ($optionAttributes as $optionAttribute) {
            /** @var \MageWorx\OptionBase\Api\ImportInterface $optionAttribute */
            $optionAttribute->validateTemplateMageTwo($data);
        }
    }

    /**
     * Validate group's option values integrity
     *
     * @param array $data
     * @throws LocalizedException
     */
    protected function validateValues($data)
    {
        if ((!isset($data['values']) || !is_array($data['values']))) {
            if (in_array($data['type'], $this->baseHelper->getSelectableOptionTypes())) {
                throw new LocalizedException(
                    __("Selectable option doesn't have values")
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
                __("'%1' option value's field '%2' not found or empty", $this->currentTemplateName, $field)
            );
        }
        foreach ($this->valueValidationRules['required'] as $field) {
            if (isset($data[$field])) {
                continue;
            }
            throw new LocalizedException(
                __("'%1' option value's field '%2' not found", $this->currentTemplateName, $field)
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
            $valueAttribute->validateTemplateMageTwo($data);
        }
    }

    /**
     * Validate customer groups and store IDs data, because they may be different for M1 and M2
     *
     * @param array $data
     * @throws IntegrationException
     */
    protected function validateSystemData($data)
    {
        foreach ($data as $dataItem) {
            $this->collectSystemDataFromOptions($dataItem);
        }

        if ($this->isSystemDataEquivalentMapNeeded()) {
            $this->storeIds         = $this->storeIdMap;
            $this->customerGroupIds = $this->customerGroupMap;
            throw new IntegrationException(
                __("Please, link system specific data for Magento/Magento 2 and upload the file once again.")
            );
        } elseif ($this->hasSystemDataEquivalentMapDuplicates()) {
            $this->storeIds         = $this->storeIdMap;
            $this->customerGroupIds = $this->customerGroupMap;
            throw new IntegrationException(
                __("Please, avoid assignment of stores and customer groups to the same entities.")
            );
        }
    }

    /**
     * Check if it is needed to set system data equivalent map (M1 to M2)
     *
     * @return bool
     */
    protected function isSystemDataEquivalentMapNeeded()
    {
        return $this->hasMagentoSystemData() && !$this->isSystemDataEquivalentMapExist();
    }

    /**
     * Check if equivalent map has duplicates of selected values
     *
     * @return bool
     */
    protected function hasSystemDataEquivalentMapDuplicates()
    {
        if ($this->hasMagentoSystemData() && !$this->isSystemDataEquivalentMapExist()) {
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
    protected function hasMagentoSystemData()
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
     * @param array $data
     */
    protected function collectSystemDataFromOptions($data)
    {
        if (!isset($data['options']) || !is_array($data['options'])) {
            return;
        }

        $options          = $data['options'];
        $optionAttributes = $this->optionAttributes->getData();
        $valueAttributes  = $this->valueAttributes->getData();

        foreach ($options as $optionData) {
            foreach ($optionAttributes as $optionAttribute) {
                /** @var \MageWorx\OptionBase\Api\ImportInterface $optionAttribute */
                $systemData = $optionAttribute->collectTemplateSystemDataMageTwo($optionData);
                if (!$systemData) {
                    continue;
                }
                if (!empty($systemData['store'])) {
                    $this->storeIdMap += $systemData['store'];
                }
                if (!empty($systemData['customer_group'])) {
                    $this->customerGroupMap += $systemData['customer_group'];
                }
            }

            if ((!isset($optionData['values']) || !is_array($optionData['values']))) {
                continue;
            }

            foreach ($optionData['values'] as $valueData) {
                foreach ($valueAttributes as $valueAttribute) {
                    /** @var \MageWorx\OptionBase\Api\ImportInterface $valueAttribute */
                    $systemData = $valueAttribute->collectTemplateSystemDataMageTwo($valueData);
                    if (!$systemData) {
                        continue;
                    }
                    if (!empty($systemData['store'])) {
                        $this->storeIdMap += $systemData['store'];
                    }
                    if (!empty($systemData['customer_group'])) {
                        $this->customerGroupMap += $systemData['customer_group'];
                    }
                }
            }
        }
    }

    /**
     * Import data
     *
     * @param array $data
     */
    protected function importData($data)
    {
        foreach ($data as $dataItem) {
            $this->importGroup($dataItem);
        }
    }

    /**
     * Import data for group
     *
     * @param array $data
     */
    protected function importGroup($data)
    {
        /** @var $group \MageWorx\OptionTemplates\Model\Group */
        $group                       = $this->groupFactory->create();
        $this->currentMagentoGroupId = $data['group_id'];

        if (!isset($data['options'])) {
            $data['options'] = [];
            $group->setData($data);
            $group->unsetData($group->getIdFieldName());
            $group->setId(null);
        } else {
            $options = $data['options'];
            if (is_array($options)) {
                $preparedOptions = $this->prepareOptions($options);
                if ($preparedOptions) {
                    $data['options']         = $preparedOptions;
                    $data['product_options'] = $preparedOptions;
                    $group->setOptions($preparedOptions);
                }
            }
            $group->setData($data);
            $group->unsetData($group->getIdFieldName());
            $group->setId(null);
            $group->setCanSaveCustomOptions(true);
        }

        $group->setIsUniqueTitleNeeded(true);
        $group->save();
        $this->applyTemplateToProducts($group);
    }

    /**
     * Prepare option's data for group import
     * Remove option_ids/option_type_ids
     * Add record_id to simulate dynamicRows saving
     *
     * @param array $fromOptions
     * @return array
     */
    protected function prepareOptions($fromOptions)
    {
        $preparedOptions = [];

        if (!is_array($fromOptions)) {
            return [];
        }
        $sortOrderCounter = 1;
        foreach ($fromOptions as $fromOption) {

            $optionData = [
                'record_id'  => $fromOption['option_id'],
                'sort_order' => (string)$sortOrderCounter
            ];

            $standardOptionFields = [
                'title',
                'type',
                'is_require',
                'price',
                'sku',
                'max_characters',
                'file_extension',
                'image_size_x',
                'image_size_y'
            ];
            foreach ($standardOptionFields as $standardOptionField) {
                if (isset($fromOption[$standardOptionField])) {
                    $optionData[$standardOptionField] = $fromOption[$standardOptionField];
                }
            }
            if (isset($fromOption['price_type'])) {
                $optionData['price_type'] = $fromOption['price_type'] == 'fixed' ? 'fixed' : 'percent';
            }

            $this->prepareTitles($fromOption);
            $this->preparePrices($fromOption);
            $this->prepareDescriptions($fromOption);
            $this->prepareOptionStoresData($fromOption);
            $this->prepareOptionCustomerGroupsData($fromOption);
            $this->checkImages($fromOption);

            $optionAttributes = $this->optionAttributes->getData();
            foreach ($optionAttributes as $optionAttribute) {
                /** @var \MageWorx\OptionBase\Api\ImportInterface $optionAttribute */
                $optionData[$optionAttribute->getName()] = $optionAttribute->importTemplateMageTwo($fromOption);
            }
            if (isset($fromOption['values']) && is_array($fromOption['values'])) {
                $optionData['values'] = $this->prepareOptionValues($fromOption);
            }

            $preparedOptions[] = $this->groupOptionFactory->create()->setData($optionData);
            $sortOrderCounter++;
        }

        return $preparedOptions;
    }

    /**
     * Prepare data for different store views
     *
     * @param array $fromOption
     * @return void
     */
    protected function prepareDescriptions(&$fromOption)
    {
        if (!isset($fromOption['option_id']) || empty($fromOption['description'])) {
            return;
        }

        $storeOptionDescriptions = $this->baseHelper->jsonDecode($fromOption['description']);
        if (!is_array($storeOptionDescriptions)) {
            return;
        }

        $descriptions = [];
        foreach ($storeOptionDescriptions as $storeOptionDescription) {
            if (isset($this->storeEquivalentMap[$storeOptionDescription['store_id']])
                && $this->storeEquivalentMap[$storeOptionDescription['store_id']] !== ''
            ) {
                $descriptions[] = [
                    'description' => $storeOptionDescription['description'],
                    'store_id'    => $this->storeEquivalentMap[$storeOptionDescription['store_id']]
                ];
            } elseif ($storeOptionDescription['store_id'] === '0') {
                $descriptions[] = [
                    'description' => $storeOptionDescription['description'],
                    'store_id'    => '0'
                ];
            }
        }
        $fromOption['description'] = $this->baseHelper->jsonEncode($descriptions);

        if (empty($fromOption['values'])) {
            return;
        }

        foreach ($fromOption['values'] as &$value) {

            if (!isset($value['option_type_id']) || empty($value['description'])) {
                continue;
            }

            $storeValueDescriptions = $this->baseHelper->jsonDecode($value['description']);
            if (!is_array($storeValueDescriptions)) {
                continue;
            }

            $descriptions = [];
            foreach ($storeValueDescriptions as $storeValueDescription) {
                if (isset($this->storeEquivalentMap[$storeValueDescription['store_id']])
                    && $this->storeEquivalentMap[$storeValueDescription['store_id']] !== ''
                ) {
                    $descriptions[] = [
                        'description' => $storeValueDescription['description'],
                        'store_id'    => $this->storeEquivalentMap[$storeValueDescription['store_id']]
                    ];
                } elseif ($storeValueDescription['store_id'] === '0') {
                    $descriptions[] = [
                        'description' => $storeValueDescription['description'],
                        'store_id'    => '0'
                    ];
                }
            }
            $value['description'] = $this->baseHelper->jsonEncode($descriptions);
        }
    }

    /**
     * Prepare data for different store views
     *
     * @param array $fromOption
     * @return void
     */
    protected function prepareTitles(&$fromOption)
    {
        if (!isset($fromOption['option_id']) || empty($fromOption['mageworx_title'])) {
            return;
        }

        $storeTitles = $this->baseHelper->jsonDecode($fromOption['mageworx_title']);
        if (!is_array($storeTitles)) {
            return;
        }

        $titles = [];
        foreach ($storeTitles as $storeTitle) {
            if (isset($this->storeEquivalentMap[$storeTitle['store_id']])
                && $this->storeEquivalentMap[$storeTitle['store_id']] !== ''
            ) {
                $titles[] = [
                    'title'    => $storeTitle['title'],
                    'store_id' => $this->storeEquivalentMap[$storeTitle['store_id']]
                ];
            } elseif ($storeTitle['store_id'] === '0') {
                $titles[] = [
                    'title'    => $storeTitle['title'],
                    'store_id' => '0'
                ];
            }
        }
        $fromOption['mageworx_title'] = $this->baseHelper->jsonEncode($titles);

        if (empty($fromOption['values'])) {
            return;
        }

        foreach ($fromOption['values'] as &$value) {

            if (!isset($value['option_type_id']) || empty($value['mageworx_title'])) {
                continue;
            }

            $storeValueTitles = $this->baseHelper->jsonDecode($value['mageworx_title']);
            if (!is_array($storeValueTitles)) {
                continue;
            }

            $titles = [];
            foreach ($storeValueTitles as $storeValueTitle) {
                if (isset($this->storeEquivalentMap[$storeValueTitle['store_id']])
                    && $this->storeEquivalentMap[$storeValueTitle['store_id']] !== ''
                ) {
                    $titles[] = [
                        'title'    => $storeValueTitle['title'],
                        'store_id' => $this->storeEquivalentMap[$storeValueTitle['store_id']]
                    ];
                } elseif ($storeValueTitle['store_id'] === '0') {
                    $titles[] = [
                        'title'    => $storeValueTitle['title'],
                        'store_id' => '0'
                    ];
                }
            }
            $value['mageworx_title'] = $this->baseHelper->jsonEncode($titles);
        }
    }

    /**
     * Prepare data for different store views
     *
     * @param array $fromOption
     * @return void
     */
    protected function preparePrices(&$fromOption)
    {
        if (!isset($fromOption['option_id']) || empty($fromOption['mageworx_option_price'])) {
            return;
        }

        $storeOptionData = $this->baseHelper->jsonDecode($fromOption['mageworx_option_price']);
        if (!is_array($storeOptionData)) {
            return;
        }

        $data = [];
        foreach ($storeOptionData as $storeOptionDatum) {
            if (isset($this->storeEquivalentMap[$storeOptionDatum['store_id']])
                && $this->storeEquivalentMap[$storeOptionDatum['store_id']] !== ''
            ) {
                $data[] = [
                    'price'    => $storeOptionDatum['price'],
                    'store_id' => $this->storeEquivalentMap[$storeOptionDatum['store_id']]
                ];
            } elseif ($storeOptionDatum['store_id'] === '0') {
                $data[] = [
                    'price'    => $storeOptionDatum['price'],
                    'store_id' => '0'
                ];
            }
        }
        $fromOption['mageworx_option_price'] = $this->baseHelper->jsonEncode($data);

        if (empty($fromOption['values'])) {
            return;
        }

        foreach ($fromOption['values'] as &$value) {

            if (!isset($value['option_type_id']) || empty($value['mageworx_option_type_price'])) {
                continue;
            }

            $storeValueData = $this->baseHelper->jsonDecode($value['mageworx_option_type_price']);
            if (!is_array($storeValueData)) {
                continue;
            }

            $data = [];
            foreach ($storeValueData as $storeValueDatum) {
                if (isset($this->storeEquivalentMap[$storeValueDatum['store_id']])
                    && $this->storeEquivalentMap[$storeValueDatum['store_id']] !== ''
                ) {
                    $data[] = [
                        'price'    => $storeValueDatum['price'],
                        'store_id' => $this->storeEquivalentMap[$storeValueDatum['store_id']]
                    ];
                } elseif ($storeValueDatum['store_id'] === '0') {
                    $data[] = [
                        'price'    => $storeValueDatum['price'],
                        'store_id' => '0'
                    ];
                }
            }
            $value['mageworx_option_type_price'] = $this->baseHelper->jsonEncode($data);
        }
    }

    /**
     * Change stores data to M2 equivalent
     *
     * @param array $optionData
     * @return void
     */
    protected function prepareOptionStoresData(&$optionData)
    {
        if (empty($optionData['store_view']) || !is_string($optionData['store_view'])) {
            $optionData['store_view'] = null;

            return;
        }

        $storeViews = $this->baseHelper->jsonDecode($optionData['store_view']);
        if (!is_array($storeViews)) {
            $optionData['store_view'] = null;

            return;
        }

        $stores    = [];
        $isEnabled = 0;
        foreach ($storeViews as $storeView) {
            if (isset($this->storeEquivalentMap[$storeView['customer_store_id']])
                && $this->storeEquivalentMap[$storeView['customer_store_id']] !== ''
            ) {
                $stores[]                  = $this->storeEquivalentMap[$storeView['customer_store_id']];
                $optionData['is_disabled'] = 0;
                $isEnabled                 = 1;
            } elseif (!$isEnabled) {
                $optionData['is_disabled'] = 1;
            }
        }
        $optionData['store_view'] = $stores;
    }

    /**
     * Change customer groups data to M2 equivalent
     *
     * @param array $optionData
     * @return void
     */
    protected function prepareOptionCustomerGroupsData(&$optionData)
    {
        if (empty($optionData['customer_group']) || !is_string($optionData['customer_group'])) {
            $optionData['customer_group'] = null;

            return;
        }

        $customerGroups = $this->baseHelper->jsonDecode($optionData['customer_group']);
        if (!is_array($customerGroups)) {
            $optionData['customer_group'] = null;

            return;
        }

        $groups    = [];
        $isEnabled = 0;
        foreach ($customerGroups as $customerGroup) {
            if (isset($this->customerEquivalentMap[$customerGroup['customer_group_id']])
                && $this->customerEquivalentMap[$customerGroup['customer_group_id']] !== ''
            ) {
                $groups[]                  = $this->customerEquivalentMap[$customerGroup['customer_group_id']];
                $optionData['is_disabled'] = 0;
                $isEnabled                 = 1;
            } elseif (!$isEnabled) {
                $optionData['is_disabled'] = 1;
            }
        }
        $optionData['customer_group'] = $groups;
    }

    /**
     * Prepare value's data for group import
     *
     * @param array $fromOption
     * @return array
     */
    protected function prepareOptionValues($fromOption)
    {
        $preparedOptionValues = [];

        if (!isset($fromOption['values']) || !is_array($fromOption['values'])) {
            return [];
        }
        $sortOrderCounter = 1;
        foreach ($fromOption['values'] as $fromValue) {
            $valueData = [
                'record_id'  => $fromValue['option_type_id'],
                'price_type' => $fromValue['price_type'] == 'fixed' ? 'fixed' : 'percent',
                'price'      => $fromValue['price'],
                'title'      => $fromValue['title'],
                'sku'        => $fromValue['sku'],
                'sort_order' => (string)$sortOrderCounter
            ];

            $this->prepareValueStoreSpecificData($fromValue, 'special_price');
            $this->prepareValueStoreSpecificData($fromValue, 'tier_price');
            $this->prepareSkuIsValidData($fromValue);

            $valueAttributes = $this->valueAttributes->getData();
            foreach ($valueAttributes as $valueAttribute) {
                /** @var \MageWorx\OptionBase\Api\ImportInterface $valueAttribute */
                $valueData[$valueAttribute->getName()] = $valueAttribute->importTemplateMageTwo($fromValue);
            }

            $preparedOptionValues[] = $valueData;
            $sortOrderCounter++;
        }

        return $preparedOptionValues;
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareSkuIsValidData(array & $value): void
    {
        if ($value['sku'] &&
            $this->optionValueSkuValidator->isOptionValueSkuIsValid($value['sku'])
        ) {
            $value['sku_is_valid'] = true;
        } else {
            $value['sku_is_valid'] = false;
        }
    }

    /**
     * Change customer groups to M2 equivalent
     *
     * @param array $value
     * @param string $key
     * @return void
     */
    protected function prepareValueStoreSpecificData(&$value, $key)
    {
        if (empty($value[$key]) || !is_string($value[$key])) {
            $value[$key] = null;

            return;
        }

        $valueData = $this->baseHelper->jsonDecode($value[$key]);
        if (!is_array($valueData)) {
            $value[$key] = null;

            return;
        }

        $data = [];
        foreach ($valueData as $valueDatum) {
            if (isset($this->customerEquivalentMap[$valueDatum['customer_group_id']])
                && $this->customerEquivalentMap[$valueDatum['customer_group_id']] !== ''
            ) {
                $valueDatum['customer_group_id'] = $this->customerEquivalentMap[$valueDatum['customer_group_id']];
                $data[]                          = $valueDatum;
            }
        }
        $value[$key] = $data;
    }

    /**
     * Check image presence in M2 media APO directory
     *
     * @param array $option
     * @return void
     * @throws FileSystemException
     */
    protected function checkImages($option)
    {
        if (empty($option['values']) || !is_array($option['values'])) {
            return;
        }
        foreach ($option['values'] as &$value) {
            if (empty($value['images_data']) || !is_string($value['images_data'])) {
                $value['images_data'] = null;
                continue;
            }

            $imagesData = $this->baseHelper->jsonDecode($value['images_data']);

            if (empty($imagesData) || !is_array($imagesData)) {
                continue;
            }
            foreach ($imagesData as $imagesDatum) {
                if (!isset($imagesDatum['value'])) {
                    continue;
                }

                $filePath = 'mageworx/optionfeatures/product/option/value' . $imagesDatum['value'];

                try {
                    $this->mediaDirectory->renameFile($filePath, $filePath);
                } catch (FileSystemException $e) {
                    if ($this->helper->isIgnoreMissingImages()) {
                        $this->missingImagesList[] = $filePath;
                        continue;
                    } else {
                        $this->storeIds         = $this->storeIdMap;
                        $this->customerGroupIds = $this->customerGroupMap;
                        throw new FileSystemException(__("The '%1' file doesn't exist.", $filePath));
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
    public function getAssignedProducts()
    {
        return $this->assignedProducts;
    }

    /**
     * @return array
     */
    public function getMissingSkus()
    {
        return $this->missingSkus;
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
}
