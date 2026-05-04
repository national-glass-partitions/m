<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionImportExport\Model\MageTwo\Export;

use \Magento\CatalogImportExport\Model\Export\Product as DefaultExport;
use \Magento\ImportExport\Model\Import;
use \Magento\Store\Model\Store;
use \Magento\CatalogImportExport\Model\Import\Product as ImportProduct;
use MageWorx\OptionBase\Model\Product\Attributes as ProductAttributes;
use MageWorx\OptionBase\Model\Product\Option\Attributes as OptionAttributes;
use MageWorx\OptionBase\Model\Product\Option\Value\Attributes as ValueAttributes;
use MageWorx\OptionTemplates\Model\ResourceModel\Group as GroupResourceModel;

class Product extends DefaultExport
{
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
     * @var GroupResourceModel
     */
    protected $groupResourceModel;

    /**
     * @var string
     */
    protected $entityTypeCode;

    /**
     * @var array
     */
    protected $defaultOptionColumns = [
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

    /**
     * @var array
     */
    protected $defaultValueColumns = [
        'custom_option_row_id',
        'custom_option_row_title',
        'custom_option_row_price',
        'custom_option_row_price_type',
        'custom_option_row_sku',
        'custom_option_row_sort',
        'custom_option_row_group_id'
    ];

    /**
     * @var array
     */
    protected $mageworxProductColumns = [];

    /**
     * @var array
     */
    protected $mageworxOptionColumns = [];

    /**
     * @var array
     */
    protected $mageworxValueColumns = [];

    /**
     * Product constructor.
     *
     * @param GroupResourceModel $groupResourceModel
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Eav\Model\Config $config
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory
     * @param \Magento\ImportExport\Model\Export\ConfigInterface $exportConfig
     * @param \Magento\Catalog\Model\ResourceModel\ProductFactory $productFactory
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $attrSetColFactory
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryColFactory
     * @param \Magento\CatalogInventory\Model\ResourceModel\Stock\ItemFactory $itemFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product\Option\CollectionFactory $optionColFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $attributeColFactory
     * @param \Magento\CatalogImportExport\Model\Export\Product\Type\Factory $_typeFactory
     * @param \Magento\Catalog\Model\Product\LinkTypeProvider $linkTypeProvider
     * @param \Magento\CatalogImportExport\Model\Export\RowCustomizerInterface $rowCustomizer
     * @param array $dateAttrCodes
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        GroupResourceModel $groupResourceModel,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Eav\Model\Config $config,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory,
        \Magento\ImportExport\Model\Export\ConfigInterface $exportConfig,
        \Magento\Catalog\Model\ResourceModel\ProductFactory $productFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $attrSetColFactory,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryColFactory,
        \Magento\CatalogInventory\Model\ResourceModel\Stock\ItemFactory $itemFactory,
        \Magento\Catalog\Model\ResourceModel\Product\Option\CollectionFactory $optionColFactory,
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $attributeColFactory,
        \Magento\CatalogImportExport\Model\Export\Product\Type\Factory $_typeFactory,
        \Magento\Catalog\Model\Product\LinkTypeProvider $linkTypeProvider,
        \Magento\CatalogImportExport\Model\Export\RowCustomizerInterface $rowCustomizer,
        ProductAttributes $productAttributes,
        OptionAttributes $optionAttributes,
        ValueAttributes $valueAttributes,
        array $dateAttrCodes = []
    ) {
        $this->productAttributes  = $productAttributes;
        $this->optionAttributes   = $optionAttributes;
        $this->valueAttributes    = $valueAttributes;
        $this->groupResourceModel = $groupResourceModel;
        parent::__construct(
            $localeDate,
            $config,
            $resource,
            $storeManager,
            $logger,
            $collectionFactory,
            $exportConfig,
            $productFactory,
            $attrSetColFactory,
            $categoryColFactory,
            $itemFactory,
            $optionColFactory,
            $attributeColFactory,
            $_typeFactory,
            $linkTypeProvider,
            $rowCustomizer,
            $dateAttrCodes
        );
        $this->initTypeModels();
    }

    /**
     * Get Entity type code
     *
     * @return string
     */
    public function getEntityTypeCode()
    {
        if (!$this->entityTypeCode) {
            $this->entityTypeCode = 'catalog_product';
        } else {
            $this->entityTypeCode = 'catalog_product_with_apo';
        }
        return $this->entityTypeCode;
    }

    /**
     * Export process
     *
     * @return mixed
     */
    public function export()
    {
        //Execution time may be very long
        set_time_limit(0);

        $this->setWriter($this->getWriter());
        $writer = $this->getWriter();
        $page   = 0;
        while (true) {
            ++$page;
            $entityCollection = $this->_getEntityCollection(true);
            $entityCollection->setOrder('entity_id', 'asc');
            $entityCollection->setStoreId(0);
            $this->_prepareEntityCollection($entityCollection);
            $this->paginateCollection($page, $this->getItemsPerPage());
            if ($entityCollection->count() == Store::DEFAULT_STORE_ID) {
                break;
            }
            $this->setEntityCollection($entityCollection);
            $this->exportData($page);
            if ($entityCollection->getCurPage() >= $entityCollection->getLastPageNumber()) {
                break;
            }
        }
        return $writer->getContents();
    }

    protected function setEntityCollection($collection)
    {
        $this->_entityCollection = $collection;
    }

    /**
     * Get advanced custom options data
     *
     * @param int[] $productIds
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function getAdvancedCustomOptionsData($productIds)
    {
        $customOptionsData = [];

        foreach (array_keys($this->_storeIdToCode) as $storeId) {
            if (Store::DEFAULT_STORE_ID != $storeId) {
                continue;
            }
            $options = $this->_optionColFactory->create();
            /* @var \Magento\Catalog\Model\ResourceModel\Product\Option\Collection $options */
            $options->reset()->addOrder(
                'sort_order',
                'ASC'
            )->addTitleToResult(
                $storeId
            )->addPriceToResult(
                $storeId
            )->addProductToFilter(
                $productIds
            )->addValuesToResult(
                $storeId
            );

            foreach ($options as $option) {
                $productId = $option['product_id'];

                $row = $this->mapOptionFields($option);

                $values = $option->getValues();

                if ($values) {
                    $index = 0;
                    foreach ($values as $value) {
                        if ($index > 0) {
                            $row = [];
                        }
                        $this->mapValueFields($row, $value);

                        $customOptionsData[$productId][$storeId][] = $row;
                        $index++;
                    }
                } else {
                    $customOptionsData[$productId][$storeId][] = $row;
                }
                $option = null;
            }
            $options = null;
        }

        return $customOptionsData;
    }

    /**
     * Map option fields
     *
     * @param array $option
     * @return array
     */
    protected function mapOptionFields($option)
    {
        $row = [];

        $optionPriceType = ($option['price_type'] == 'percent') ? $option['price_type'] : 'fixed';

        $row['custom_option_id']          = $option['option_id'];
        $row['custom_option_title']       = $option['title'];
        $row['custom_option_type']        = $option['type'];
        $row['custom_option_is_required'] = $option['is_require'];
        $row['custom_option_price']       = $option['price'];
        $row['custom_option_price_type']  = $optionPriceType;
        $row['custom_option_sku']         = $option['sku'];
        $row['custom_option_sort_order']  = $option['sort_order'];
        $row['custom_option_group_id']    = $option['group_option_id'];
        if ($option['file_extension']) {
            $row['custom_option_file_extension'] = $option['file_extension'];
        }
        if ($option['image_size_x']) {
            $row['custom_option_image_size_x'] = $option['image_size_x'];
        }
        if ($option['image_size_y']) {
            $row['custom_option_image_size_y'] = $option['image_size_y'];
        }
        if ($option['max_characters']) {
            $row['custom_option_max_characters'] = $option['max_characters'];
        }

        $optionAttributes = $this->optionAttributes->getData();
        foreach ($optionAttributes as $optionAttribute) {
            /** @var \MageWorx\OptionBase\Api\ImportInterface $optionAttribute */
            $optionAttribute->collectExportDataMageTwo($row, $option);
            $this->mageworxOptionColumns[] = 'custom_option_' . $optionAttribute->getName();
        }

        return $row;
    }

    /**
     * Map value fields
     *
     * @param array $row
     * @param array $value
     * @return void
     */
    protected function mapValueFields(&$row, $value)
    {
        $valuePriceType = ($value['price_type'] == 'percent') ? $value['price_type'] : 'fixed';

        $row['custom_option_row_id']         = $value['option_type_id'];
        $row['custom_option_row_title']      = $value['title'];
        $row['custom_option_row_price']      = $value['price'];
        $row['custom_option_row_price_type'] = $valuePriceType;
        $row['custom_option_row_sku']        = $value['sku'];
        $row['custom_option_row_sort']       = $value['sort_order'];
        $row['custom_option_row_group_id']   = $value['group_option_value_id'];

        $valueAttributes = $this->valueAttributes->getData();
        foreach ($valueAttributes as $valueAttribute) {
            /** @var \MageWorx\OptionBase\Api\ImportInterface $valueAttribute */
            $valueAttribute->collectExportDataMageTwo($row, $value);
            $this->mageworxValueColumns[] = 'custom_option_row_' . $valueAttribute->getName();
        }
    }

    /**
     * Export data to file
     *
     * @param int $page
     * @return void
     */
    protected function exportData($page)
    {
        try {
            $rawData      = $this->collectRawData();
            $multirawData = $this->collectMultirawData();

            $productIds    = array_keys($rawData);
            $stockItemRows = $this->prepareCatalogInventory($productIds);

            $this->rowCustomizer->prepareData($this->_getEntityCollection(), $productIds);

            $this->setHeaderColumns($multirawData['customOptionsData'], $stockItemRows);
            $this->_headerColumns = $this->rowCustomizer->addHeaderColumns($this->_headerColumns);
            if ($page == 1) {
                $writer = $this->getWriter();
                $writer->setHeaderCols($this->_getHeaderColumns());
            }

            foreach ($rawData as $productId => $productData) {
                foreach ($productData as $storeId => $dataRow) {
                    if ($storeId == Store::DEFAULT_STORE_ID && isset($stockItemRows[$productId])) {
                        $dataRow = array_merge($dataRow, $stockItemRows[$productId]);
                    }
                    $this->processMultirawData($dataRow, $multirawData, $productData);
                }
            }
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
    }

    /**
     * Process multiraw data
     *
     * @param array $dataRow
     * @param array $multiRawData
     * @param array $productData
     * @return void
     */
    protected function processMultirawData(&$dataRow, &$multiRawData, $productData)
    {
        $productId     = $dataRow['product_id'];
        $productLinkId = $dataRow['product_link_id'];
        $storeId       = $dataRow['store_id'];
        $sku           = $dataRow[self::COL_SKU];
        $type          = $dataRow[self::COL_TYPE];
        $attributeSet  = $dataRow[self::COL_ATTR_SET];

        unset($dataRow['product_id']);
        unset($dataRow['product_link_id']);
        unset($dataRow['store_id']);
        unset($dataRow[self::COL_SKU]);
        unset($dataRow[self::COL_STORE]);
        unset($dataRow[self::COL_ATTR_SET]);
        unset($dataRow[self::COL_TYPE]);

        if (Store::DEFAULT_STORE_ID == $storeId) {
            unset($dataRow[self::COL_STORE]);
            $this->updateDataWithCategoryColumns($dataRow, $multiRawData['rowCategories'], $productId);
            if (!empty($multiRawData['rowWebsites'][$productId])) {
                $websiteCodes = [];
                foreach ($multiRawData['rowWebsites'][$productId] as $productWebsite) {
                    $websiteCodes[] = $this->_websiteIdToCode[$productWebsite];
                }
                $dataRow[self::COL_PRODUCT_WEBSITES]     =
                    implode(Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR, $websiteCodes);
                $multiRawData['rowWebsites'][$productId] = [];
            }
            if (!empty($multiRawData['mediaGalery'][$productLinkId])) {
                $additionalImages          = [];
                $additionalImageLabels     = [];
                $additionalImageIsDisabled = [];
                foreach ($multiRawData['mediaGalery'][$productLinkId] as $mediaItem) {
                    $additionalImages[]      = $mediaItem['_media_image'];
                    $additionalImageLabels[] = $mediaItem['_media_label'];

                    if ($mediaItem['_media_is_disabled'] == true) {
                        $additionalImageIsDisabled[] = $mediaItem['_media_image'];
                    }
                }
                $dataRow['additional_images']                =
                    implode(Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR, $additionalImages);
                $dataRow['additional_image_labels']          =
                    implode(Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR, $additionalImageLabels);
                $dataRow['hide_from_product_page']           =
                    implode(Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR, $additionalImageIsDisabled);
                $multiRawData['mediaGalery'][$productLinkId] = [];
            }
            foreach ($this->_linkTypeProvider->getLinkTypes() as $linkTypeName => $linkId) {
                if (!empty($multiRawData['linksRows'][$productLinkId][$linkId])) {
                    $colPrefix = $linkTypeName . '_';

                    $associations = [];
                    foreach ($multiRawData['linksRows'][$productLinkId][$linkId] as $linkData) {
                        if ($linkData['default_qty'] !== null) {
                            $skuItem = $linkData['sku'] . ImportProduct::PAIR_NAME_VALUE_SEPARATOR .
                                $linkData['default_qty'];
                        } else {
                            $skuItem = $linkData['sku'];
                        }
                        $associations[$skuItem] = $linkData['position'];
                    }
                    $multiRawData['linksRows'][$productLinkId][$linkId] = [];
                    asort($associations);
                    $dataRow[$colPrefix . 'skus']     =
                        implode(Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR, array_keys($associations));
                    $dataRow[$colPrefix . 'position'] =
                        implode(Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR, array_values($associations));
                }
            }
            $dataRow = $this->rowCustomizer->addData($dataRow, $productId);
        }

        if (!empty($this->collectedMultiselectsData[$storeId][$productId])) {
            foreach (array_keys($this->collectedMultiselectsData[$storeId][$productId]) as $attrKey) {
                if (!empty($this->collectedMultiselectsData[$storeId][$productId][$attrKey])) {
                    $dataRow[$attrKey] = implode(
                        Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR,
                        $this->collectedMultiselectsData[$storeId][$productId][$attrKey]
                    );
                }
            }
        }

        $advancedCustomOptionsRows = [];
        if (!empty($multiRawData['advancedCustomOptionsData'][$productLinkId][$storeId])) {
            $advancedCustomOptionsRows = $multiRawData['advancedCustomOptionsData'][$productLinkId][$storeId];

            $multiRawData['advancedCustomOptionsData'][$productLinkId][$storeId] = [];

            $dataRow = array_merge(
                $dataRow,
                array_shift(
                    $advancedCustomOptionsRows
                )
            );
        }
        if (!empty($multiRawData['mageworxProductAttributes'][$productLinkId])) {
            $mageworxProductAttributesRows                             = $multiRawData['mageworxProductAttributes'][$productLinkId];
            $multiRawData['mageworxProductAttributes'][$productLinkId] = [];
            $dataRow                                                   = array_merge(
                $dataRow,
                $mageworxProductAttributesRows
            );
        }

        if (empty($dataRow)) {
            return null;
        } elseif ($storeId != Store::DEFAULT_STORE_ID) {
            $dataRow[self::COL_STORE] = $this->_storeIdToCode[$storeId];
            if (isset($productData[$storeId][self::COL_VISIBILITY])) {
                $dataRow[self::COL_VISIBILITY] = $productData[$storeId][self::COL_VISIBILITY];
            }
        }
        $dataRow[self::COL_SKU]      = $sku;
        $dataRow[self::COL_ATTR_SET] = $attributeSet;
        $dataRow[self::COL_TYPE]     = $type;

        $this->getWriter()->writeRow($this->_customFieldsMapping($dataRow));

        $additionalRowsCount = count($advancedCustomOptionsRows);
        if ($additionalRowsCount) {
            for ($i = 0; $i < $additionalRowsCount; $i++) {
                $dataRow = [];
                $dataRow = array_merge($dataRow, array_shift($advancedCustomOptionsRows));
                $this->getWriter()->writeRow($this->_customFieldsMapping($dataRow));
            }
        }
    }

    /**
     * Collect multiraw data
     *
     * @return array
     */
    protected function collectMultirawData()
    {
        $data                      = [];
        $productIds                = [];
        $rowWebsites               = [];
        $rowCategories             = [];
        $productLinkIds            = [];
        $mageworxProductAttributes = [];

        $collection = $this->_getEntityCollection();
        $collection->setStoreId(Store::DEFAULT_STORE_ID);
        $collection->addCategoryIds()->addWebsiteNamesToResult();
        /** @var \Magento\Catalog\Model\Product $item */
        foreach ($collection as $item) {
            $productLinkIds[]              = $item->getData($this->getProductEntityLinkField());
            $productIds[]                  = $item->getId();
            $rowWebsites[$item->getId()]   = array_intersect(
                array_keys($this->_websiteIdToCode),
                $item->getWebsites()
            );
            $rowCategories[$item->getId()] = array_combine($item->getCategoryIds(), $item->getCategoryIds());

            $this->mageworxProductColumns[] = 'option_templates';

            $productAttributes = $this->productAttributes->getData();
            foreach ($productAttributes as $productAttribute) {
                /** @var \MageWorx\OptionBase\Api\ProductAttributeInterface $productAttribute */
                if ($productAttribute->shouldSkipExportMageTwo()) {
                    continue;
                }
                $mageworxProductAttributes[$item->getId()][$productAttribute->getName()] = $item->getData(
                    $productAttribute->getName()
                );

                $this->mageworxProductColumns[] = $productAttribute->getName();
            }

            $groupData = $this->groupResourceModel->getGroupData($item->getData($this->getProductEntityLinkField()));

            $mageworxProductAttributes[$item->getId()]['option_templates'] = '';
            $preparedOptionTemplates                                       = [];
            if ($groupData && is_array($groupData)) {
                foreach ($groupData as $groupId => $groupTitle) {
                    $groupTitle = str_replace('|', '&separator', $groupTitle);
                    $groupTitle = str_replace('=', '&equal', $groupTitle);
                    $preparedOptionTemplates[] = $groupId . '=' . $groupTitle;
                }
                $mageworxProductAttributes[$item->getId()]['option_templates'] = implode('|', $preparedOptionTemplates);
            }

        }
        $collection->clear();

        $allCategoriesIds = array_merge(array_keys($this->_categories), array_keys($this->_rootCategories));
        $allCategoriesIds = array_combine($allCategoriesIds, $allCategoriesIds);
        foreach ($rowCategories as &$categories) {
            $categories = array_intersect_key($categories, $allCategoriesIds);
        }

        $data['rowWebsites']   = $rowWebsites;
        $data['rowCategories'] = $rowCategories;
        $data['mediaGalery']   = $this->getMediaGallery($productLinkIds);
        $data['linksRows']     = $this->prepareLinks($productLinkIds);

        $data['customOptionsData']         = $this->getCustomOptionsData($productLinkIds);
        $data['advancedCustomOptionsData'] = $this->getAdvancedCustomOptionsData($productLinkIds);
        $data['mageworxProductAttributes'] = $mageworxProductAttributes;

        return $data;
    }

    /**
     * Set headers columns
     *
     * @param array $customOptionsData
     * @param array $stockItemRows
     * @return void
     */
    protected function setHeaderColumns($customOptionsData, $stockItemRows)
    {
        if (!$this->_headerColumns) {
            $customOptCols        = [
                'custom_options',
            ];
            $this->_headerColumns = array_merge(
                [
                    self::COL_SKU,
                    self::COL_STORE,
                    self::COL_ATTR_SET,
                    self::COL_TYPE,
                    self::COL_CATEGORY,
                    self::COL_PRODUCT_WEBSITES,
                ],
                $this->_getExportMainAttrCodes(),
                [self::COL_ADDITIONAL_ATTRIBUTES],
                reset($stockItemRows) ? array_keys(end($stockItemRows)) : [],
                [],
                [
                    'related_skus',
                    'related_position',
                    'crosssell_skus',
                    'crosssell_position',
                    'upsell_skus',
                    'upsell_position'
                ],
                ['additional_images', 'additional_image_labels', 'hide_from_product_page']
            );

            if ($customOptionsData) {
                $this->_headerColumns = array_merge($this->_headerColumns, $customOptCols);
            }
            $this->_headerColumns = array_merge(
                $this->_headerColumns,
                $this->getMageWorxCustomOptionsColumns()
            );
        }
    }

    protected function getMageWorxCustomOptionsColumns()
    {
        return array_merge(
            $this->mageworxProductColumns,
            $this->defaultOptionColumns,
            $this->mageworxOptionColumns,
            $this->defaultValueColumns,
            $this->mageworxValueColumns
        );
    }
}
