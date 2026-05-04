<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionBase\Model\OptionSaver;

use MageWorx\OptionBase\Helper\Data as BaseHelper;
use Magento\Catalog\Api\Data\ProductCustomOptionInterface;
use MageWorx\OptionBase\Model\Product\Option\Value\Attributes as OptionValueAttributes;

class Value extends \Magento\Catalog\Model\ResourceModel\Product\Option\Value
{
    const TABLE_NAME_CATALOG_PRODUCT_OPTION_TYPE_VALUE = 'catalog_product_option_type_value';
    const TABLE_NAME_CATALOG_PRODUCT_OPTION_TYPE_PRICE = 'catalog_product_option_type_price';
    const TABLE_NAME_CATALOG_PRODUCT_OPTION_TYPE_TITLE = 'catalog_product_option_type_title';

    protected BaseHelper $baseHelper;
    protected OptionValueAttributes $optionValueAttributes;

    /**
     * Class constructor
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Directory\Model\CurrencyFactory $currencyFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param OptionValueAttributes $optionValueAttributes
     * @param BaseHelper $baseHelper
     * @param string $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Directory\Model\CurrencyFactory $currencyFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        OptionValueAttributes $optionValueAttributes,
        BaseHelper $baseHelper,
        $connectionName = null
    )
    {
        $this->baseHelper = $baseHelper;
        $this->optionValueAttributes = $optionValueAttributes;
        parent::__construct($context, $currencyFactory, $storeManager, $config, $connectionName);
    }

    /**
     * Collect option data before multiple insert
     * Used to increase performance for applying option template to great amount of products
     *
     * @param ProductCustomOptionInterface $option
     * @param array $optionData
     * @return void
     */
    public function collectValuesBeforeInsert(ProductCustomOptionInterface &$option, &$optionData)
    {
        if (!$option->getData('values')) {
            return;
        }

        $updatedValues = [];
        foreach ($option->getData('values') as $value) {
            if (isset($value['is_delete']) && $value['is_delete'] == true) {
                continue;
            }

            $updatedValues[] = $this->collectValueData($option, $value, $optionData);
        }
        $option->setData('values', $updatedValues);
        $option->setValues($updatedValues);

        return;
    }

    /**
     * Collect value's data
     *
     * @param ProductCustomOptionInterface $option
     * @param array $value
     * @param array $optionData
     * @return array
     */
    protected function collectValueData($option, $value, &$optionData)
    {
        $value['option_id'] = $option->getData('option_id');
        $value['group_option_id'] = $option->getData('group_option_id');

        $data = [
            'option_type_id' => $value['option_type_id'],
            'option_id' => $option->getData('option_id'),
            'group_option_value_id' => $value['group_option_value_id'],
            'sku' => isset($value['sku']) ? $value['sku'] : '',
            'sort_order' => $value['sort_order']
        ];

        foreach ($this->optionValueAttributes->getData() as $attribute) {
            if (!$attribute->hasOwnTable()) {
                if (!isset($value[$attribute->getName()])) {
                    $data[$attribute->getName()] = null;
                } else {
                    $data[$attribute->getName()] = $attribute->prepareDataBeforeSave($value);
                }
            }
        }

        $catalogProductOptionTable = $this->getTable(self::TABLE_NAME_CATALOG_PRODUCT_OPTION_TYPE_VALUE);
        $optionData[self::TABLE_NAME_CATALOG_PRODUCT_OPTION_TYPE_VALUE][$value['option_type_id']] =
            $this->_prepareDataForTable(
                new \Magento\Framework\DataObject($data),
                $catalogProductOptionTable
            );

        $this->collectPriceData($value, $optionData);
        $this->collectTitleData($value, $optionData);

        return $value;
    }

    /**
     * Collect option prices for option values before multiple insert
     *
     * @param \Magento\Catalog\Model\Product\Option\Value $value
     * @param array $optionData
     * @return void
     */
    protected function collectPriceData($value, &$optionData)
    {
        $priceTable = $this->getTable(self::TABLE_NAME_CATALOG_PRODUCT_OPTION_TYPE_PRICE);

        $price = (double)sprintf('%F', $value['price']);
        $priceType = $value['price_type'];

        if ($value['price'] && $priceType) {
            $data = $this->_prepareDataForTable(
                new \Magento\Framework\DataObject(
                    [
                        'option_type_id' => (int)$value['option_type_id'],
                        'store_id' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
                        'price' => $price,
                        'price_type' => $priceType,
                    ]
                ),
                $priceTable
            );
            $optionData[self::TABLE_NAME_CATALOG_PRODUCT_OPTION_TYPE_PRICE][$value['option_type_id']] = $this->_prepareDataForTable(
                new \Magento\Framework\DataObject($data),
                $priceTable
            );
        }
    }

    /**
     * Collect option prices for option titles before multiple insert
     *
     * @param \Magento\Catalog\Model\Product\Option\Value $value
     * @param array $optionData
     * @return void
     */
    protected function collectTitleData($value, &$optionData)
    {
        $titleTableName = $this->getTable(self::TABLE_NAME_CATALOG_PRODUCT_OPTION_TYPE_TITLE);
        $storeIds = [];
        $storeIds[] = \Magento\Store\Model\Store::DEFAULT_STORE_ID;
        foreach ($storeIds as $storeId) {
            if (!$value['title']) {
                return;
            }

            $data = $this->_prepareDataForTable(
                new \Magento\Framework\DataObject(
                    [
                        'option_type_id' => $value['option_type_id'],
                        'store_id' => $storeId,
                        'title' => $value['title'],
                    ]
                ),
                $titleTableName
            );

            $optionData[self::TABLE_NAME_CATALOG_PRODUCT_OPTION_TYPE_TITLE][$value['option_type_id']] =
                $this->_prepareDataForTable(
                    new \Magento\Framework\DataObject($data),
                    $titleTableName
                );
        }
    }
}
