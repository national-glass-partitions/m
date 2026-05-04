<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionLink\Helper;

use \MageWorx\OptionLink\Helper\Data as HelperData;
use \MageWorx\OptionBase\Model\Source\LinkedProductAttributes as LinkAttributesModel;
use \Magento\Framework\App\Helper\Context;
use \Magento\Eav\Model\Config as EavConfig;

/**
 * OptionLink Attribute Helper.
 */
class Attribute extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Map of attributes of a product which are replaced with the appropriate fields of options
     *
     * @var array|null
     */
    protected ?array $fieldsMap = null;
    protected Data $helperData;
    protected LinkAttributesModel $linkAttributesModel;
    protected EavConfig $eavConfig;

    public function __construct(
        HelperData $helperData,
        LinkAttributesModel $linkAttributesModel,
        Context $context,
        EavConfig $eavConfig
    ) {
        $this->helperData          = $helperData;
        $this->linkAttributesModel = $linkAttributesModel;
        $this->eavConfig           = $eavConfig;
        parent::__construct($context);
    }

    /**
     * @param $attributeName
     * @return int|mixed|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isAttributeExist($attributeCode)
    {
        return true; // temporary fix

        return $this->eavConfig->getAttribute('catalog_product', $attributeCode)->getAttributeId();
    }

    /**
     * Check if product attribute selected for replace field of option
     *
     * @param $attribute
     * @return bool
     */
    public function isAttributeSelected($attribute)
    {
        return in_array($attribute, $this->getConvertedAttributesToFields());
    }

    /**
     * Retrieve selected product attributes like fields of option
     *
     * @return array
     */
    public function getConvertedAttributesToFields()
    {
        $attributes = $this->helperData->getLinkedProductAttributesAsArray();

        if (!$attributes) {
            return $attributes;
        }

        return $this->convertAttributesToFields($attributes);
    }

    /**
     * Retrieve ALL products attributes (used in "Link Assigned Product's Attributes" setting)
     * as option value fields
     *
     * @return array
     */
    public function getAllLinkAttributesAsFields()
    {
        $result     = [];
        $attributes = $this->linkAttributesModel->toOptionArray();

        foreach ($attributes as $attribute) {
            $result[] = $attribute['value'];
        }

        return $this->convertAttributesToFields($result);
    }

    /**
     * Retrieve fields map array.
     * This array is used for:
     * - convert product attributes to option value fileds;
     * - join product attributes;
     *
     * @return array
     */
    public function getFieldsMap(): array
    {
        if (null === $this->fieldsMap) {
            $object = new \Magento\Framework\DataObject();
            $object->setFieldsMap($this->getDefaultFieldsMap());
            $this->_eventManager->dispatch('mw_optionlink_helper_attribute_prepare_fields_map', ['object' => $object]);

            $this->fieldsMap = $object->getFieldsMap();
        }

        return $this->fieldsMap;
    }

    /**
     * This method converts product attributes
     * to option value fileds by fieldsMap.
     *
     * @param array $attributes
     * @return array
     */
    protected function convertAttributesToFields($attributes)
    {
        foreach ($this->getFieldsMap() as $key => $value) {
            $id = array_search($key, $attributes);
            if ($id !== false) {
                $attributes[$id] = $value['option_name'];
            }
        }

        return $attributes;
    }

    protected function getDefaultFieldsMap(): array
    {
        return [
            'name'   => [
                'option_name' => 'title',
                'type'        => 'attribute',
                'joinType'    => 'left',
            ],
            'price'  => [
                'option_name' => 'price',
                'type'        => 'attribute',
                'joinType'    => 'left',
            ],
            'cost'   => [
                'option_name' => 'cost',
                'type'        => 'attribute',
                'joinType'    => 'left',
            ],
            'weight' => [
                'option_name' => 'weight',
                'type'        => 'attribute',
                'joinType'    => 'left',
            ],
            'qty'    => [
                'option_name' => 'qty',
                'type'        => 'field',
                'alias'       => 'qty',
                'table'       => 'cataloginventory_stock_item',
                'field'       => 'qty',
                'bind'        => 'product_id=entity_id',
                'cond'        => '{{table}}.stock_id=1',
                'joinType'    => 'left',
            ]
        ];
    }
}
