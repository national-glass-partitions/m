<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionBase\Plugin;

use Magento\Framework\View\DesignInterface;

class ChangeTemplateForOptionTypeFile
{
    protected DesignInterface $design;

    public function __construct(
        DesignInterface $design
    ) {
        $this->design = $design;
    }

    /**
     * @param $subject \Magento\Catalog\Block\Product\View\Options\Type\File
     * @param $result string
     * @return string
     */
    public function afterGetTemplate($subject, $result)
    {
        $theme = $this->design->getDesignTheme();

        while ($theme) {
            if ($theme->getCode() === 'Amasty/JetTheme') {
                return 'MageWorx_OptionBase::product/view/options/type/file.phtml';
            }

            $theme = $theme->getParentTheme();
        }

        return $result;
    }
}
