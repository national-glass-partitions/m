<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionTemplates\Ui\DataProvider\Group\Form\Modifier;

use Magento\Ui\DataProvider\Modifier\ModifierInterface;

abstract class AbstractModifier implements ModifierInterface
{
    const FORM_NAME = 'mageworx_optiontemplates_group';
    const DATA_SOURCE_DEFAULT = 'mageworx_optiontemplates_group';
    const DATA_SCOPE_GROUP = 'data.mageworx_optiontemplates_group';
}
