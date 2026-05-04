<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionFeatures\Model\Attribute\OptionValue;

use Magento\Framework\DataObjectFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Serialize\Serializer\Json as Serializer;
use Magento\Store\Model\Store;
use Magento\Cms\Model\Template\FilterProvider as FilterProvider;
use MageWorx\OptionBase\Helper\System as SystemHelper;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionBase\Model\Product\Option\AbstractAttribute;
use MageWorx\OptionFeatures\Helper\Data as Helper;
use MageWorx\OptionFeatures\Model\OptionTypeDescription;
use MageWorx\OptionFeatures\Model\ResourceModel\OptionTypeDescription\Collection as DescriptionCollection;
use MageWorx\OptionFeatures\Model\OptionTypeDescriptionFactory as DescriptionFactory;

class Description extends AbstractAttribute
{
    const FIELD_MAGE_ONE_OPTIONS_IMPORT = '_custom_option_row_description';

    protected FilterProvider $filterProvider;
    protected Helper $helper;
    protected SystemHelper $systemHelper;
    protected DescriptionFactory $descriptionFactory;
    protected DescriptionCollection $descriptionCollection;
    protected Serializer $serializer;

    /**
     * @param FilterProvider $filterProvider
     * @param ResourceConnection $resource
     * @param DescriptionFactory $descriptionFactory
     * @param DescriptionCollection $descriptionCollection
     * @param BaseHelper $baseHelper
     * @param DataObjectFactory $dataObjectFactory
     * @param Helper $helper
     * @param SystemHelper $systemHelper
     * @param Serializer $serializer
     */
    public function __construct(
        FilterProvider $filterProvider,
        ResourceConnection $resource,
        DescriptionFactory $descriptionFactory,
        DescriptionCollection $descriptionCollection,
        Helper $helper,
        BaseHelper $baseHelper,
        DataObjectFactory $dataObjectFactory,
        SystemHelper $systemHelper,
        Serializer $serializer
    ) {
        $this->helper                = $helper;
        $this->filterProvider        = $filterProvider;
        $this->systemHelper          = $systemHelper;
        $this->descriptionFactory    = $descriptionFactory;
        $this->descriptionCollection = $descriptionCollection;
        $this->serializer            = $serializer;
        parent::__construct($resource, $baseHelper, $dataObjectFactory);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return Helper::KEY_DESCRIPTION;
    }

    /**
     * {@inheritdoc}
     */
    public function hasOwnTable()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getTableName($type = '')
    {
        $map = [
            'product' => OptionTypeDescription::TABLE_NAME,
            'group'   => OptionTypeDescription::OPTIONTEMPLATES_TABLE_NAME
        ];

        return $type ? $map[$type] : $map[$this->entity->getType()];
    }

    /**
     * {@inheritdoc}
     */
    public function collectData($entity, array $options)
    {
        if (!$this->helper->isValueDescriptionEnabled() && !$this->baseHelper->isAPOImportAction()) {
            return [];
        }

        $this->entity = $entity;

        $descriptions = [];
        foreach ($options as $option) {
            if (empty($option['values'])) {
                continue;
            }
            foreach ($option['values'] as $value) {
                if (!isset($value[$this->getName()])) {
                    continue;
                }
                $descriptions[$value['option_type_id']] = $value[$this->getName()];
            }
        }

        return $this->collectDescriptions($descriptions);
    }

    /**
     * Collect descriptions
     *
     * @param array $items
     * @return array
     */
    protected function collectDescriptions($items)
    {
        $data = [];

        foreach ($items as $itemKey => $itemValue) {
            $data['delete'][] = [
                OptionTypeDescription::COLUMN_NAME_OPTION_TYPE_ID => $itemKey,
            ];
            $decodedJsonData  = $itemValue ? $this->serializer->unserialize(
                preg_replace('/\r?\n/', '', $itemValue)
            ) : null;
            if (empty($decodedJsonData) || !is_array($decodedJsonData)) {
                continue;
            }
            foreach ($decodedJsonData as $dataItem) {
                $description = str_replace(PHP_EOL, '', $dataItem[OptionTypeDescription::COLUMN_NAME_DESCRIPTION]);
                $description = str_replace('\\', '', $description);
                $description = preg_replace('/[[:cntrl:]]/', ' ', (string)$description);
                if ($description === '') {
                    continue;
                }
                $data['save'][] = [
                    OptionTypeDescription::COLUMN_NAME_OPTION_TYPE_ID => $itemKey,
                    OptionTypeDescription::COLUMN_NAME_STORE_ID       =>
                        $dataItem[OptionTypeDescription::COLUMN_NAME_STORE_ID],
                    OptionTypeDescription::COLUMN_NAME_DESCRIPTION    =>
                        htmlspecialchars($description, ENT_COMPAT, 'UTF-8', false)
                ];
            }
        }
        if (!$data) {
            return [];
        }

        return $data;
    }

    /**
     * Delete old option value description
     *
     * @param array $data
     * @return void
     */
    public function deleteOldData(array $data)
    {
        $optionValueIds = [];
        foreach ($data as $dataItem) {
            $optionValueIds[] = $dataItem[OptionTypeDescription::COLUMN_NAME_OPTION_TYPE_ID];
        }
        if (!$optionValueIds) {
            return;
        }
        $tableName  = $this->resource->getTableName($this->getTableName());
        $conditions = OptionTypeDescription::COLUMN_NAME_OPTION_TYPE_ID .
            " IN (" . "'" . implode("','", $optionValueIds) . "'" . ")";
        $this->resource->getConnection()->delete($tableName, $conditions);
    }

    /**
     * Prepare attribute data for frontend js config
     *
     * @param \Magento\Catalog\Model\Product\Option|\Magento\Catalog\Model\Product\Option\Value $object
     * @return array
     */
    public function prepareDataForFrontend($object)
    {
        $storeId = $this->systemHelper->resolveCurrentStoreId();

        $jsonDataToDecode = $object->getData($this->getName());
        $decodedJsonData = $jsonDataToDecode ? $this->serializer->unserialize($jsonDataToDecode) : null;

        if (empty($decodedJsonData) || !is_array($decodedJsonData)) {
            return [$this->getName() => ''];
        }

        $description             = '';
        $defaultStoreDescription = '';
        foreach ($decodedJsonData as $dataItem) {
            if ($dataItem[OptionTypeDescription::COLUMN_NAME_STORE_ID] == 0) {
                $defaultStoreDescription = $dataItem[OptionTypeDescription::COLUMN_NAME_DESCRIPTION];
            }
            if ($dataItem[OptionTypeDescription::COLUMN_NAME_STORE_ID] == $storeId) {
                $description = $dataItem[OptionTypeDescription::COLUMN_NAME_DESCRIPTION];
            }
        }
        $description        = $description ?: $defaultStoreDescription;
        $decodedDescription = $this->filterProvider->getPageFilter()->filter(htmlspecialchars_decode($description));

        return [$this->getName() => $decodedDescription];
    }

    /**
     * Process attribute in case of product/group duplication
     *
     * @param string $newId
     * @param string $oldId
     * @param string $entityType
     * @return void
     */
    public function processDuplicate($newId, $oldId, $entityType = 'product')
    {
        $connection = $this->resource->getConnection();
        $table      = $this->resource->getTableName($this->getTableName($entityType));

        $select = $connection->select()->from(
            $table,
            [
                new \Zend_Db_Expr($newId),
                OptionTypeDescription::COLUMN_NAME_STORE_ID,
                OptionTypeDescription::COLUMN_NAME_DESCRIPTION
            ]
        )->where(
            OptionTypeDescription::COLUMN_NAME_OPTION_TYPE_ID . ' = ?',
            $oldId
        );

        $insertSelect = $connection->insertFromSelect(
            $select,
            $table,
            [
                OptionTypeDescription::COLUMN_NAME_OPTION_TYPE_ID,
                OptionTypeDescription::COLUMN_NAME_STORE_ID,
                OptionTypeDescription::COLUMN_NAME_DESCRIPTION
            ]
        );
        $connection->query($insertSelect);
    }

    /**
     * {@inheritdoc}
     */
    public function importTemplateMageOne($data)
    {
        $descriptions = [];
        if (empty($data['description'])) {
            return '';
        }
        if (is_array($data['description'])) {
            foreach ($data['description'] as $datum) {
                $descriptions[] = [
                    OptionTypeDescription::COLUMN_NAME_STORE_ID    => $datum['store_id'],
                    OptionTypeDescription::COLUMN_NAME_DESCRIPTION => $datum['description']
                ];
            }
        } else {
            $descriptions[] = [
                OptionTypeDescription::COLUMN_NAME_STORE_ID    => Store::DEFAULT_STORE_ID,
                OptionTypeDescription::COLUMN_NAME_DESCRIPTION => $data['description']
            ];
        }

        return $this->serializer->serialize($descriptions);
    }

    /**
     * {@inheritdoc}
     */
    public function importTemplateMageTwo($data)
    {
        return isset($data[$this->getName()]) ? $data[$this->getName()] : null;
    }

    /**
     * Collect system data (customer group ids, store ids) from Magento 1 product csv
     *
     * @param array $systemData
     * @param array $productData
     * @param array $optionData
     * @param array $valueData
     */
    public function collectOptionsSystemDataMageOne(&$systemData, $productData, $optionData, $valueData = [])
    {
        if (empty($valueData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT])
            || !is_array($valueData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT])
        ) {
            return;
        }

        foreach ($valueData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT] as $datumStore => $datumValue) {
            $systemData['store'][$datumStore] = $datumStore;
        }
    }

    /**
     * Collect system data (customer group ids, store ids) from Magento 2 template data
     *
     * @param array $data
     * @return array
     */
    public function collectTemplateSystemDataMageTwo($data)
    {
        return $this->collectStoresDataByKey($data, 'description');
    }

    /**
     * Prepare data from Magento 1 product csv for future import
     *
     * @param array $systemData
     * @param array $productData
     * @param array $optionData
     * @param array $preparedOptionData
     * @param array $valueData
     * @param array $preparedValueData
     * @return void
     */
    public function prepareOptionsMageOne(
        $systemData,
        $productData,
        $optionData,
        &$preparedOptionData,
        $valueData = [],
        &$preparedValueData = []
    ) {
        if (empty($valueData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT])
            || !is_array($valueData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT])
        ) {
            return;
        }

        $data = [];
        foreach ($valueData[static::FIELD_MAGE_ONE_OPTIONS_IMPORT] as $datumStore => $datumValue) {
            if (!$this->hasStoreEquivalent($systemData, $datumStore)) {
                continue;
            }
            $data[] = [
                OptionTypeDescription::COLUMN_NAME_STORE_ID    => $systemData['map']['store'][$datumStore],
                OptionTypeDescription::COLUMN_NAME_DESCRIPTION => $datumValue,
            ];
        }
        $preparedValueData[static::getName()] = $this->baseHelper->jsonEncode($data);
    }

    /**
     * Collect data for magento2 product export
     *
     * @param array $row
     * @param array $data
     * @return void
     */
    public function collectExportDataMageTwo(&$row, $data)
    {
        $prefix        = 'custom_option_row_';
        $attributeData = null;
        if (!empty($data[$this->getName()])) {
            $attributeData = $this->baseHelper->jsonDecode($data[$this->getName()]);
        }
        if (empty($attributeData) || !is_array($attributeData)) {
            $row[$prefix . $this->getName()] = null;

            return;
        }
        $result = [];
        foreach ($attributeData as $datum) {
            $parts = [];
            foreach ($datum as $datumKey => $datumValue) {
                $datumValue = $this->encodeSymbols($datumValue);
                $parts[]    = $datumKey . '=' . $datumValue . '';
            }
            $result[] = implode(',', $parts);
        }
        $row[$prefix . $this->getName()] = $result ? implode('|', $result) : null;
    }

    /**
     * Collect data for magento2 product import
     *
     * @param array $data
     * @return array|null
     */
    public function collectImportDataMageTwo($data)
    {
        if (!$this->hasOwnTable()) {
            return null;
        }

        if (!isset($data['custom_option_row_' . $this->getName()])) {
            return null;
        }

        $this->entity = $this->dataObjectFactory->create();
        $this->entity->setType('product');

        $descriptions = [];
        $preparedData = [];
        $iterator     = 0;

        $attributeData = $data['custom_option_row_' . $this->getName()];
        if (empty($attributeData)) {
            return $this->collectDescriptions($descriptions);
        }

        $step1 = explode('|', $attributeData);
        foreach ($step1 as $step1Item) {
            $step2 = explode(',', $step1Item);
            foreach ($step2 as $step2Item) {
                $step3Item                              = explode('=', $step2Item);
                $step3Item[1]                           = $this->decodeSymbols($step3Item[1]);
                $preparedData[$iterator][$step3Item[0]] = $step3Item[1];
            }
            $iterator++;
        }
        $descriptions[$data['custom_option_row_id']] = $this->baseHelper->jsonEncode($preparedData);

        return $this->collectDescriptions($descriptions);
    }

    /**
     * Get value description data
     *
     * @param array $optionTypeIds
     * @return array
     */
    public function getValueAttributesData(array $optionTypeIds): array
    {
        /* Can get description data for current storeId in future
         * At this time all description data needed for: API, GraphQL, Admin product page, Import/Export m2
         */
        $connection             = $this->resource->getConnection();
        $optionTypeDescriptions = $connection->select()->from(
            $this->resource->getTableName(OptionTypeDescription::TABLE_NAME),
        )->where('option_type_id IN (?)', $optionTypeIds);

        return $this->transformToJson($optionTypeDescriptions->query()->fetchAll());
    }

    /**
     * Transform descriptions to json like:
     * [{"store_id":"0","description":"test"},{"store_id":"1","description":"default
     * test"},{"store_id":"2","description":"nl_NL test"}]
     *
     * @param array $optionTypeDescriptionsData
     * @return array
     */
    protected function transformToJson(array $optionTypeDescriptionsData): array
    {
        $descriptions = [];
        foreach ($optionTypeDescriptionsData as $item) {
            $descriptions[$item['option_type_id']][] =
                [
                    OptionTypeDescription::COLUMN_NAME_STORE_ID    => $item['store_id'],
                    OptionTypeDescription::COLUMN_NAME_DESCRIPTION => $item['description']
                ];
        }
        $result = [];

        foreach ($descriptions as $key => $description) {
            $result[$key] = $this->serializer->serialize($description);
        }
        return $result;
    }
}
