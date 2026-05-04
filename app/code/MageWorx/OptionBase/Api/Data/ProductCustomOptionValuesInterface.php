<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionBase\Api\Data;

/**
 * @api
 */
interface ProductCustomOptionValuesInterface extends \Magento\Catalog\Api\Data\ProductCustomOptionValuesInterface,
    \Magento\Framework\Api\ExtensibleDataInterface
{
    /**
     * Retrieve existing extension attributes object.
     *
     * @return \MageWorx\OptionBase\Api\Data\ProductCustomOptionValuesExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     *
     * @param \MageWorx\OptionBase\Api\Data\ProductCustomOptionValuesExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \MageWorx\OptionBase\Api\Data\ProductCustomOptionValuesExtensionInterface $extensionAttributes
    );
}
