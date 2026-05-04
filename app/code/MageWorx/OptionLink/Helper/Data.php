<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionLink\Helper;

use \Magento\Store\Model\ScopeInterface;
use \Magento\Framework\App\Helper\Context;
use \MageWorx\OptionBase\Helper\Data as HelperBase;
use \MageWorx\OptionFeatures\Helper\Data as FeaturesHelper;

/**
 * OptionLink Data Helper.
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const KEY_SKU_IS_VALID = 'sku_is_valid';

    /**
     * XML config path linked product attributes by SKU
     */
    const XML_PATH_LINKED_PRODUCT_ATTRIBUTES = 'mageworx_apo/optionlink/linked_product_attributes';

    protected HelperBase $helperBase;
    protected FeaturesHelper $featuresHelper;
    protected ?array $linkedProductAttributesByStoreId;

    /**
     * Data constructor.
     *
     * @param HelperBase $helperBase
     * @param FeaturesHelper $featuresHelper
     * @param Context $context
     */
    public function __construct(
        HelperBase $helperBase,
        FeaturesHelper $featuresHelper,
        Context $context
    ) {
        $this->helperBase = $helperBase;
        $this->featuresHelper = $featuresHelper;
        parent::__construct($context);
    }

    /**
     * Retrieve comma-separated linked product attributes
     *
     * @param int|null $storeId
     * @return string
     */
    public function getLinkedProductAttributes(int $storeId = null): string
    {
        $storeIdKey = ($storeId === null) ? 'null' : $storeId;

        if (!isset($this->linkedProductAttributesByStoreId[$storeIdKey])) {
            $linkedProductAttributes = (string)$this->scopeConfig->getValue(
                self::XML_PATH_LINKED_PRODUCT_ATTRIBUTES,
                ScopeInterface::SCOPE_STORE,
                $storeId
            );

            $object = new \Magento\Framework\DataObject();
            $object->setLinkedProductAttributes($linkedProductAttributes);
            $this->_eventManager->dispatch(
                'mw_optionlink_helper_data_prepare_linked_product_attributes',
                ['object' => $object, 'store_id' => $storeId]
            );

            $this->linkedProductAttributesByStoreId[$storeIdKey] = $object->getLinkedProductAttributes();
        }

        return $this->linkedProductAttributesByStoreId[$storeIdKey];
    }

    /**
     * Retrieve list of linked product attributes
     *
     * @param int|null $storeId
     * @return array
     */
    public function getLinkedProductAttributesAsArray(int $storeId = null): array
    {
        $linkedProductAttributes = $this->getLinkedProductAttributes($storeId);
        if (!$linkedProductAttributes) {
            return [];
        }
        $result = explode(',', $linkedProductAttributes);

        $validatedResult = [];
        foreach ($result as $resultItem) {
            if ((!$this->featuresHelper->isWeightEnabled() && $resultItem == FeaturesHelper::KEY_WEIGHT)
                || (!$this->featuresHelper->isCostEnabled() && $resultItem == FeaturesHelper::KEY_COST)
            ) {
                continue;
            }
            $validatedResult[] = $resultItem;
        }

        return $this->helperBase->prepareLinkedAttributes($validatedResult);
    }
}
