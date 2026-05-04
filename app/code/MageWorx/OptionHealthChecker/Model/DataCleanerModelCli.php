<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See https://www.mageworx.com/terms-and-conditions for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionHealthChecker\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use MageWorx\OptionBase\Helper\Data as HelperBase;
use MageWorx\OptionHealthChecker\Api\DataCleanerModelInterface;
use MageWorx\OptionHealthChecker\Helper\Data as HelperData;
use MageWorx\OptionHealthChecker\Model\ResourceModel\DataCleanerProcess;

class DataCleanerModelCli implements DataCleanerModelInterface
{
    /* Add new APO tables manually in the future.
       To guarantee that all our tables will be cleaned, we are adding our tables manually.
    */
    protected array $entitiesTables = [
        'mageworx_option_dependency',
        'mageworx_optionbase_product_attributes'
    ];

    /* Our bug, in this tables we are using relation only with entity_id */
    protected array $specificEntitiesTables = [
        'mageworx_dynamic_options',
        'mageworx_optiontemplates_relation'
    ];

    protected array $optionsTables = [
        'mageworx_optionfeatures_option_description',
        'mageworx_optionvisibility_option_customer_group',
        'mageworx_optionvisibility_option_store_view'
    ];

    protected array $optionValuesTables = [
        'mageworx_optionadvancedpricing_option_type_special_price',
        'mageworx_optionadvancedpricing_option_type_tier_price',
        'mageworx_optionfeatures_option_type_description',
        'mageworx_optionfeatures_option_type_image',
        'mageworx_optionfeatures_option_type_is_default'
    ];

    protected array $groupsTemplateTables = [
        'mageworx_optiontemplates_group_option',
        'mageworx_optiontemplates_group_option_dependency',
        'mageworx_optiontemplates_relation'
    ];

    protected array $optionsTemplateTables = [
        'mageworx_optiontemplates_group_option_customer_group',
        'mageworx_optiontemplates_group_option_description',
        'mageworx_optiontemplates_group_option_price',
        'mageworx_optiontemplates_group_option_type_value',
        'mageworx_optiontemplates_group_option_store_view',
        'mageworx_optiontemplates_group_option_title'
    ];

    protected array $optionValuesTemplateTables = [
        'mageworx_optiontemplates_group_option_type_description',
        'mageworx_optiontemplates_group_option_type_image',
        'mageworx_optiontemplates_group_option_type_is_default',
        'mageworx_optiontemplates_group_option_type_price',
        'mageworx_optiontemplates_group_option_type_special_price',
        'mageworx_optiontemplates_group_option_type_tier_price',
        'mageworx_optiontemplates_group_option_type_title'
    ];

    protected array $dependencyTable = [
        'mageworx_option_dependency'
    ];

    protected array $templatesDependencyTable = [
        'mageworx_optiontemplates_group_option_dependency'
    ];

    protected array $emptyDataTables = [
        'mageworx_optionfeatures_option_description'             => 'description',
        'mageworx_optionfeatures_option_type_description'        => 'description',
        'mageworx_optionfeatures_option_type_is_default'         => 'is_default',
        'mageworx_optiontemplates_group_option_description'      => 'description',
        'mageworx_optiontemplates_group_option_type_is_default'  => 'is_default',
        'mageworx_optiontemplates_group_option_type_description' => 'description'
    ];

    protected array $analyzeDataArray = [];
    protected DataCleanerProcess $dataCleanerProcess;
    protected HelperData $helperData;
    protected AdapterInterface $connection;
    protected ResourceConnection $resource;
    protected HelperBase $helperBase;

    public function __construct(
        DataCleanerProcess $dataCleanerProcess,
        HelperData $helperData,
        ResourceConnection $resource,
        HelperBase $helperBase
    ) {
        $this->dataCleanerProcess = $dataCleanerProcess;
        $this->helperData         = $helperData;
        $this->resource           = $resource;
        $this->helperBase         = $helperBase;
    }

    /**
     * @param bool $isAnalyzeData
     * @return array
     * @throws \Exception
     */
    public function dataCleanerHandler(bool $isAnalyzeData): array
    {
        $this->setIsTablesValid(false);
        $queriesToClearDataMap = $this->getQueryParamsToCleanNonRelatedRowsMap();
        $this->nonRelatedRecordsDataCleaningProcess($queriesToClearDataMap, $isAnalyzeData);
        $this->nonRelatedDataCleaningProcess($this->emptyDataTables, $isAnalyzeData);
        $dependencyTablesData = array_merge($this->dependencyTable, $this->templatesDependencyTable);
        $this->nonRelatedDependencyOptionCleaningProcess($dependencyTablesData, $isAnalyzeData);

        if ($isAnalyzeData && empty($this->analyzeDataArray)) {
            $this->analyzeDataArray[] = '<info>' . __('All tables data are correct') . '</info>';
            $this->setIsTablesValid(true);
        }

        return $this->analyzeDataArray;
    }

    public function getQueryParamsToCleanNonRelatedRowsMap(): array
    {
        return [
            [
                'subSelectTable'  => 'catalog_product_option_type_value',
                'subSelectColumn' => 'option_type_id',
                'targetTables'    => $this->optionValuesTables,
                'targetColumn'    => 'option_type_id'
            ],
            [
                'subSelectTable'  => 'catalog_product_option',
                'subSelectColumn' => 'option_id',
                'targetTables'    => $this->optionsTables,
                'targetColumn'    => 'option_id'
            ],
            [
                'subSelectTable'  => 'catalog_product_entity',
                'subSelectColumn' => $this->helperBase->isEnterprise() ? 'row_id' : 'entity_id',
                'targetTables'    => $this->entitiesTables,
                'targetColumn'    => 'product_id'
            ],
            [
                'subSelectTable'  => 'catalog_product_entity',
                'subSelectColumn' => 'entity_id',
                'targetTables'    => $this->specificEntitiesTables,
                'targetColumn'    => 'product_id'
            ],
            [
                'subSelectTable'  => 'mageworx_optiontemplates_group_option_type_value',
                'subSelectColumn' => 'option_type_id',
                'targetTables'    => $this->optionValuesTemplateTables,
                'targetColumn'    => 'option_type_id'
            ],
            [
                'subSelectTable'  => 'mageworx_optiontemplates_group_option',
                'subSelectColumn' => 'option_id',
                'targetTables'    => $this->optionsTemplateTables,
                'targetColumn'    => 'option_id'
            ],
            [
                'subSelectTable'  => 'mageworx_optiontemplates_group',
                'subSelectColumn' => 'group_id',
                'targetTables'    => $this->groupsTemplateTables,
                'targetColumn'    => 'group_id'
            ],
            [
                'subSelectTable'  => 'catalog_product_option',
                'subSelectColumn' => 'option_id',
                'targetTables'    => $this->dependencyTable,
                'targetColumn'    => 'dp_parent_option_id'
            ],
            [
                'subSelectTable'  => 'mageworx_optiontemplates_group_option',
                'subSelectColumn' => 'option_id',
                'targetTables'    => $this->templatesDependencyTable,
                'targetColumn'    => 'dp_parent_option_id'
            ],
        ];
    }

    /**
     * Non-product related data cleaning process
     *
     * @param array $queriesToClearDataMap
     * @param bool $isAnalyzeData
     * @return void
     */
    public function nonRelatedRecordsDataCleaningProcess(array $queriesToClearDataMap, bool $isAnalyzeData): void
    {
        $connection = $this->resource->getConnection();

        foreach ($queriesToClearDataMap as $dataToSelect) {
            $subSelectTable = $dataToSelect['subSelectTable'];
            if (!$this->isTableExists($subSelectTable)) {
                continue;
            }

            $subSelect = $connection->select()->from(
                $connection->getTableName($subSelectTable),
                $dataToSelect['subSelectColumn']
            );

            foreach ($dataToSelect['targetTables'] as $targetTable) {
                if (!$this->isTableExists($targetTable)) {
                    continue;
                }

                $mainSelect = $this->dataCleanerProcess->queryConstructor(
                    $targetTable,
                    $connection,
                    $subSelect,
                    $dataToSelect['targetColumn']
                );

                if ($isAnalyzeData) {
                    $rowCount = count($connection->fetchAssoc($mainSelect));
                    if ($rowCount) {
                        $targetColumn = $dataToSelect['targetColumn'];
                        if ($this->helperData->isPlural($rowCount)) {
                            $this->analyzeDataArray[] = ' ~ <fg=yellow>' . $rowCount .
                                '</> ' . __('rows are found in') . ' <fg=green>' . $targetTable . __(' table.') .
                                '</> ' . __('Some') . ' <fg=blue>' . $targetColumn . 's</> ' . __('no longer exist.');
                        } else {
                            $this->analyzeDataArray[] = ' ~ <fg=yellow>' . $rowCount .
                                '</> ' . __('rows is found in ') . '<fg=green>' . $targetTable . __(' table.') .
                                '</> ' . __('Some') . ' <fg=blue>' . $targetColumn . 's</> ' . __('no longer exist.');
                        }
                    }
                } else {
                    $connection->beginTransaction();
                    try {
                        $connection->query($mainSelect->deleteFromSelect($targetTable));
                        $connection->commit();
                        $this->setIsTablesValid(true);
                    } catch (\Exception $e) {
                        $connection->rollBack();
                        $this->analyzeDataArray[] = '<error>' . $e->getMessage() . '</error>';
                    }
                }
            }
        }
    }

    /**
     * Non-related data cleaning process
     *
     * @param array $emptyDataTables
     * @param bool $isAnalyzeData
     * @return void
     * @throws \Exception
     */
    public function nonRelatedDataCleaningProcess(array $emptyDataTables, bool $isAnalyzeData): void
    {
        $connection = $this->resource->getConnection();

        foreach ($emptyDataTables as $table => $column) {
            if (!$this->isTableExists($table)) {
                continue;
            }

            $select = $this->dataCleanerProcess->selectEmptyRows($connection, $table, $column);
            if ($isAnalyzeData) {
                $rowCount = count($connection->fetchAssoc($select));
                if ($rowCount) {
                    if ($this->helperData->isPlural($rowCount)) {
                        $this->analyzeDataArray[] = ' ~ <fg=yellow>' . $rowCount .
                            '</> ' . __('rows are found in') . ' <fg=green>' . $table .
                            '</> ' . __('table don\'t have useful data.');
                    } else {
                        $this->analyzeDataArray[] = ' ~ <fg=yellow>' . $rowCount .
                            '</> ' . __('rows is found in') . ' <fg=green>' . $table .
                            '</> ' . __('table doesn\'t have useful data.');
                    }
                }
            } else {
                $connection->beginTransaction();
                try {
                    $this->dataCleanerProcess->deleteEmptyRows($connection, $table, $select);
                    $connection->commit();
                    $this->setIsTablesValid(true);
                } catch (\Exception $e) {
                    $connection->rollBack();
                    $this->analyzeDataArray[] = '<error>' . $e->getMessage() . '</error>';
                }
            }
        }
    }

    /**
     * Non-related dependency option data cleaning process
     *
     * @param array $dependencyTablesData
     * @param bool $isAnalyzeData
     * @return void
     * @throws \Exception
     */
    public function nonRelatedDependencyOptionCleaningProcess(array $dependencyTablesData, bool $isAnalyzeData): void
    {
        $connection = $this->resource->getConnection();
        foreach ($dependencyTablesData as $table) {
            if (!$this->isTableExists($table)) {
                continue;
            }

            $select = $this->dataCleanerProcess->selectSpecificDependencyRows($connection, $table);
            if ($isAnalyzeData) {
                $rowCount = count($connection->fetchAssoc($select));
                if ($rowCount) {
                    $childOptIdString     = '<fg=blue>child_option_ids</>';
                    $childOptTypeIdString = '<fg=blue>child_option_type_ids</>';
                    if ($this->helperData->isPlural($rowCount)) {
                        $this->analyzeDataArray[] = ' ~ <fg=yellow>' . $rowCount .
                            '</> ' . __('rows are found in') . ' <fg=green>' . $table . '</> ' .
                            __(
                                'table don\'t have %1 while %2 are existing. ',
                                $childOptIdString,
                                $childOptTypeIdString
                            );
                    } else {
                        $this->analyzeDataArray[] = ' ~ <fg=yellow>' . $rowCount .
                            '</> ' . __('row is found in') . ' <fg=green>' . $table . '</> ' .
                            __(
                                'table doesn\'t have %1 while %2 is existing. ',
                                $childOptIdString,
                                $childOptTypeIdString
                            );
                    }
                }
            } else {
                $connection->beginTransaction();
                try {
                    $this->dataCleanerProcess->deleteSpecificDependencyRows($connection, $table, $select);
                    $connection->commit();
                    $this->setIsTablesValid(true);
                } catch (\Exception $e) {
                    $connection->rollBack();
                    $this->analyzeDataArray[] = '<error>' . $e->getMessage() . '</error>';
                }
            }
        }
    }

    public function setIsTablesValid(bool $value): void
    {
        $this->helperData->setIsTablesValid($value);
    }

    protected function isTableExists(string $tableName): bool
    {
        $connection = $this->resource->getConnection();

        return $connection->isTableExists($connection->getTableName($tableName));
    }
}
