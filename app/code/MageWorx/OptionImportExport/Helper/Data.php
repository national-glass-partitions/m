<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionImportExport\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
    const XML_PATH_IGNORE_MISSING_IMAGES = 'mageworx_apo/optionimportexport/ignore_missing_images';

    /**
     * @return bool
     */
    public function isIgnoreMissingImages()
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_IGNORE_MISSING_IMAGES
        );
    }
}
