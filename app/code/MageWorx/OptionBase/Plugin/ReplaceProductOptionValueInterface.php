<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionBase\Plugin;


class ReplaceProductOptionValueInterface
{
    /**
     * @param \Magento\Framework\Webapi\ServiceInputProcessor $subject
     * @param mixed $data
     * @param string $type
     * @return mixed
     */
    public function beforeConvertValue(\Magento\Framework\Webapi\ServiceInputProcessor $subject, $data, string $type)
    {
        $class = '\Magento\Catalog\Api\Data\ProductCustomOptionValuesInterface';
        if ($type === $class.'[]' && !is_subclass_of($class, '\Magento\Framework\Api\ExtensibleDataInterface')) {
            $type = '\MageWorx\OptionBase\Api\Data\ProductCustomOptionValuesInterface[]';
        }
        return [$data, $type];

    }

    public function beforeGetMethodsMap(\Magento\Framework\Reflection\MethodsMap $subject, string $interfaceName): array
    {
        if ($interfaceName === '\Magento\Catalog\Api\Data\ProductCustomOptionValuesInterface'
            && !is_subclass_of($interfaceName, '\Magento\Framework\Api\ExtensibleDataInterface')
        ) {
            $interfaceName = '\MageWorx\OptionBase\Api\Data\ProductCustomOptionValuesInterface';
        }
        return [$interfaceName];
    }
}