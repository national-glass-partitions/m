<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionBase\Model\Entity;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\App\Config\ScopeConfigInterface;
use MageWorx\OptionBase\Model\Product\Option\Attributes as OptionAttributes;
use MageWorx\OptionBase\Model\Product\Option\Value\Attributes as OptionValueAttributes;
use Magento\Catalog\Model\Product\Option;
use Magento\Catalog\Model\Product\Option\Value;
use MageWorx\OptionBase\Helper\Data as OptionBaseHelper;

class Base extends AbstractModel
{
    protected OptionBaseHelper $helper;
    protected ScopeConfigInterface $scopeConfig;
    protected OptionAttributes $optionAttributes;
    protected OptionValueAttributes $optionValueAttributes;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param OptionAttributes $optionAttributes
     * @param OptionValueAttributes $optionValueAttributes
     * @param OptionBaseHelper $helper
     * @param Context $context
     * @param Registry $registry
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        OptionAttributes $optionAttributes,
        OptionValueAttributes $optionValueAttributes,
        OptionBaseHelper $helper,
        Context $context,
        Registry $registry,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->scopeConfig           = $scopeConfig;
        $this->optionAttributes      = $optionAttributes;
        $this->optionValueAttributes = $optionValueAttributes;
        $this->helper                = $helper;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    public function getBaseHelper()
    {
        return $this->helper;
    }

    /**
     * Convert product or group options to array and retrieve it.
     *
     * @param mixed $object
     * @return array
     */
    public function getOptionsAsArray($object)
    {
        $options = $object->getOptions();

        if ($options == null) {
            $options = [];
        }

        $showPrice = true;
        $results   = [];

        foreach ($options as $option) {
            /* @var $option Option */
            $result                    = [];
            $result['id']              = $option->getOptionId();
            $result['item_count']      = $object->getItemCount();
            $result['option_id']       = $option->getOptionId();
            $result['title']           = $option->getTitle();
            $result['type']            = $option->getType();
            $result['is_require']      = $option->getIsRequire();
            $result['sort_order']      = $option->getSortOrder();
            $result['can_edit_price']  = $object->getCanEditPrice();
            $result['group_option_id'] = $option->getGroupOptionId();
            if (!empty($object->getGroupId())) {
                $result['group_id'] = $object->getGroupId();
            }

            if ($option->getGroupByType() == Option::OPTION_GROUP_SELECT &&
                $option->getValues()
            ) {
                $itemCount = 0;
                foreach ($option->getValues() as $value) {
                    $i = $value->getOptionTypeId();
                    /* @var $value Value */
                    $result['values'][$i] = [
                        'item_count'            => max($itemCount, $value->getOptionTypeId()),
                        'option_id'             => $value->getOptionId(),
                        'option_type_id'        => $value->getOptionTypeId(),
                        'title'                 => $value->getTitle(),
                        'price'                 => $showPrice ?
                            $this->getPriceValue((float)$value->getPrice(), $value->getPriceType()) :
                            0,
                        'price_type'            => $showPrice && $value->getPriceType() ?
                            $value->getPriceType() :
                            'fixed',
                        'sku'                   => $value->getSku(),
                        'sort_order'            => $value->getSortOrder(),
                        'group_option_value_id' => $value->getGroupOptionValueId(),
                    ];
                    if (!empty($object->getGroupId())) {
                        $result['values'][$i]['group_id'] = $object->getGroupId();
                    }
                    // Add option value attributes specified in the third-party modules to the option values
                    $result['values'][$i] = $this->addSpecificOptionValueAttributes($result['values'][$i], $value);
                }
            } else {
                $result['price']          = $showPrice ? $this->getPriceValue(
                    (float)$option->getPrice(),
                    $option->getPriceType()
                ) : 0;
                $result['price_type']     = $option->getPriceType() ? $option->getPriceType() : 'fixed';
                $result['sku']            = $option->getSku();
                $result['max_characters'] = $option->getMaxCharacters();
                $result['file_extension'] = $option->getFileExtension();
                $result['image_size_x']   = $option->getImageSizeX();
                $result['image_size_y']   = $option->getImageSizeY();
                $result['values']         = null;
            }

            // Add option attributes specified in the third-party modules to the option
            $result                          = $this->addSpecificOptionAttributes($result, $option);
            $results[$option->getOptionId()] = $result;
        }

        return $results;
    }

    /**
     * @param float $value
     * @param string $type
     * @return string
     */
    public function getPriceValue($value, $type)
    {
        if ($type == Value::TYPE_PERCENT) {
            $result = number_format($value, 2, null, '');
        } elseif ($type == 'fixed') {
            $result = number_format($value, 2, null, '');
        } elseif ($type == 'char') {
            $result = number_format($value, 2, null, '');
        } else {
            $result = 0;
        }

        return $result;
    }

    /**
     * Get option_id from option
     *
     * @param $option
     * @return string
     */
    protected function getOptionId($option)
    {
        return $option->getOptionId();
    }

    /**
     * Get option_type_id from option value
     *
     * @param $option
     * @return string
     */
    protected function getOptionTypeId($value)
    {
        return $value->getOptionTypeId();
    }

    /**
     * Get option_id from option
     *
     * @param $option
     * @return string
     */
    protected function getGroupOptionId($option)
    {
        return $option->getOptionId();
    }

    /**
     * Get option_type_id from option value
     *
     * @param $value
     * @return string
     */
    protected function getGroupOptionTypeId($value)
    {
        return $value->getOptionTypeId();
    }

    /**
     * Add specific third-party modules option attributes
     *
     * @param $result
     * @param $option
     * @return array
     */
    protected function addSpecificOptionAttributes($result, $option)
    {
        $attributes = $this->optionAttributes->getData();
        return $this->addSpecificAttributes($attributes, $result, $option);
    }

    /**
     * Add specific third-party modules option value attributes
     *
     * @param $result
     * @param $value
     * @return array
     */
    protected function addSpecificOptionValueAttributes($result, $value)
    {
        $attributes = $this->optionValueAttributes->getData();
        return $this->addSpecificAttributes($attributes, $result, $value);
    }

    /**
     * Add specific third-party modules attributes
     *
     * @param $attributes
     * @param $result
     * @param $object
     * @return array
     */
    protected function addSpecificAttributes($attributes, $result, $object)
    {
        foreach ($attributes as $attribute) {
            $attributeName = $attribute->getName();
            $data          = $object->getData();
            if (isset($data[$attributeName])) {
                $result[$attributeName] = $data[$attributeName];
            } else {
                $result[$attributeName] = '';
            }
        }
        return $result;
    }
}
