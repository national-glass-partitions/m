<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionImportExport\Plugin;

use Magento\ImportExport\Model\ResourceModel\Import\Data as DataSourceModel;
use MageWorx\OptionImportExport\Model\MageTwo\ImportProductRegistry;

class SkipRowsForImportMageTwo
{
    protected ImportProductRegistry $importProductRegistry;

    public function __construct(
        ImportProductRegistry $importProductRegistry
    ) {
        $this->importProductRegistry = $importProductRegistry;
    }

    /**
     * Get next bunch of validated rows.
     * For magento lower than 246
     *
     * @param DataSourceModel $subject
     * @param /Closure $proceed
     * @return array|null
     */
    public function aroundGetNextBunch($subject, $proceed): ?array
    {
        $dataRows = $proceed();

        return $this->getBunchOfRows($dataRows, $proceed, []);
    }

    /**
     * Get next bunch of validated rows.
     * For magento 246 and higher
     *
     */
    public function aroundGetNextUniqueBunch($subject, \Closure $proceed, array $ids): ?array
    {
        $dataRows = $proceed($ids);

        return $this->getBunchOfRows($dataRows, $proceed, $ids);
    }

    /**
     * Get another bunch of rows if all of 100 rows don't contain product SKU.
     * This is necessary to avoid end of bunch iterations during
     * \Magento\CatalogImportExport\Model\Import\Product::_saveProducts()
     *
     */
    public function getBunchOfRows($dataRows, \Closure $proceed, array $ids): ?array
    {
        if ($this->importProductRegistry->getIsOptionImport() || !$this->importProductRegistry->isProductAPOImport()) {
            return $dataRows;
        }
        if (empty($dataRows) || !is_array($dataRows)) {
            return $dataRows;
        }
        $filteredRows = $this->filterRows($dataRows);
        while (!$filteredRows && count($dataRows) === 100) {
            $dataRows     = $ids ? $proceed($ids) : $proceed();
            $filteredRows = $this->filterRows($dataRows);
        }

        return $filteredRows;
    }

    /**
     * Filter rows
     *
     * @param array $dataRows
     * @return array
     */
    public function filterRows(array $dataRows): array
    {
        $filteredRows = [];
        foreach ($dataRows as $dataRow) {
            if (isset($dataRow['sku']) && strlen(trim($dataRow['sku'])) || !empty($dataRow['_store'])) {
                $filteredRows[] = $dataRow;
            }
        }

        return $filteredRows;
    }
}
