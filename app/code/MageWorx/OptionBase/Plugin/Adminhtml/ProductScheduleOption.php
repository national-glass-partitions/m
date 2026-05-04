<?php

/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionBase\Plugin\Adminhtml;

use MageWorx\OptionBase\Helper\Data as OptionBaseHelper;
use \Magento\Framework\App\Request\Http as HttpRequest;

class ProductScheduleOption
{
    protected OptionBaseHelper $helper;
    protected HttpRequest $request;

    public function __construct(
        OptionBaseHelper $helper,
        HttpRequest $request
    ) {
        $this->helper = $helper;
        $this->request = $request;
    }

    public function beforeSave($repository, $option)
    {
        if ($this->out()) {
            return [$option];
        }

        $option->setOptionId(null);

        return [$option];
    }

    private function out()
    {
        if (!$this->request->getParam('staging')) {
            return true;
        }

        if (!$this->helper->isEnterprise()) {
            return true;
        }

        return false;
    }
}
