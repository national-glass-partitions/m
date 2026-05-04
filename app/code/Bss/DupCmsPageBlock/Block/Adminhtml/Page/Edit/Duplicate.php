<?php
/**
 * BSS Commerce Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://bsscommerce.com/Bss-Commerce-License.txt
 *
 * @category   BSS
 * @package    Bss_DupCmsPageBlock
 * @author     Extension Team
 * @copyright  Copyright (c) 2017-2018 BSS Commerce Co. ( http://bsscommerce.com )
 * @license    http://bsscommerce.com/Bss-Commerce-License.txt
 */
namespace Bss\DupCmsPageBlock\Block\Adminhtml\Page\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Cms\Block\Adminhtml\Page\Edit\GenericButton;

class Duplicate extends GenericButton implements ButtonProviderInterface
{
    /**
     * @return array
     */
    public function getButtonData()
    {
        return [
            'label' => __('Duplicate'),
            'on_click' => sprintf("location.href = '%s';", $this->getDuplicateUrl()),
            'class' => 'add',
            'sort_order' => 40
        ];
    }

    /**
     * Get URL for duplicate button
     *
     * @return string
     */
    public function getDuplicateUrl()
    {
        return $this->getUrl('dup/page/duplicate', ['page_id' => $this->getPageId()]);
    }
}
