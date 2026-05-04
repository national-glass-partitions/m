<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MageWorx\OptionFeatures\Block\Adminhtml;

use Magento\Framework\View\Element\AbstractBlock;

class GalleryContainer extends AbstractBlock
{
    /**
     * @return string
     */
    public function toHtml()
    {
        $html = '<div id="option_value_image_container"></div>';
        $html .= '<input type="hidden" id="optionfeatures_provider" value="" />';
        $html .= '<input type="hidden" id="optionfeatures_datascope" value="" />';

        return (string)$html;
    }
}
