<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionImportExport\Plugin;

use Magento\AdvancedPricingImportExport\Controller\Adminhtml\Export\GetFilter;
use Magento\AdvancedPricingImportExport\Model\Export\AdvancedPricing as ExportAdvancedPricing;
use Magento\Framework\Controller\ResultFactory;
use Magento\Catalog\Model\Product as CatalogProduct;

class UseDefaultExportProductFilter
{
    /**
     * Change entity type to get standard product filter
     *
     * @param GetFilter $subject
     * @return void
     */
    public function beforeExecute(GetFilter $subject)
    {
        $data = $subject->getRequest()->getParams();
        if ($subject->getRequest()->isXmlHttpRequest() && isset($data['entity'])) {
            if ($data['entity'] === 'catalog_product_with_apo') {
                $data['entity'] = CatalogProduct::ENTITY;
            }
            $subject->getRequest()->setParams($data);
        }
    }
}