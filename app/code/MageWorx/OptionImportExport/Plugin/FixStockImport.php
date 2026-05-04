<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionImportExport\Plugin;

use Magento\ImportExport\Model\Import\Config;

class FixStockImport extends Config
{
    /**
     * Retrieve stock import
     *
     * @param \Magento\CatalogImportExport\Model\StockItemImporterInterface $subject
     * @param callable $proceed
     * @param array $stockData
     * @return array
     */
    public function aroundImport(
        \Magento\CatalogImportExport\Model\StockItemImporterInterface $subject,
        callable $proceed,
        array $stockData
    ) {
        foreach ($stockData as $sku => $values) {
            if ($values['manage_stock'] === null) {
                unset($stockData[$sku]);
            }
        }

        if (!empty($stockData)) {
            return $proceed($stockData);
        }
    }
}
