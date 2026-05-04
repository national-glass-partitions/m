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
namespace Bss\DupCmsPageBlock\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context
    ) {
        parent::__construct($context);
    }

    /**
     * @param $id
     * @param $model
     * @param $type
     * @return mixed
     */
    public function duplicate($id, $model, $type)
    {
        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                return null;
            }
        }
        $cms = $model;
        $cmsData = $cms->getData();
        unset($cmsData[$type.'_id']);

        $cmsData['is_active'] = 0;
        $pattern = '~( - Duplicate \([0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}\))+$~i';
        $cmsData['title'] = preg_replace($pattern, '', $cmsData['title']);
        $patternIdentifier = '~(-duplicate-[0-9]{14})+$~i';
        $cmsData['identifier'] = preg_replace($patternIdentifier, '', $cmsData['identifier']);
        $cmsData['creation_time'] = date('Y-m-d H:i:s');
        $cmsData['update_time'] = date('Y-m-d H:i:s');
        $cmsData['title'] = $cmsData['title'] . ' - Duplicate (' . date('Y-m-d H:i:s') . ')';
        $cmsData['identifier'] = $cmsData['identifier'] . '-duplicate-' . date('YmdHis');

        return $cmsData;
    }
}
