<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionImportExport\Plugin;

use Magento\ImportExport\Model\Import\Config;

class ExtendImportEntitiesConfig extends Config
{
    /**
     * Retrieve export entities configuration
     *
     * @param Config $subject
     * @param array $entities
     * @return array
     */
    public function afterGetEntities(Config $subject, $entities)
    {
        if (isset($entities['catalog_product_with_apo']) && isset($entities['catalog_product'])) {
            $entities['catalog_product_with_apo']['types'] = $entities['catalog_product']['types'];
        }
        return $entities;
    }
}