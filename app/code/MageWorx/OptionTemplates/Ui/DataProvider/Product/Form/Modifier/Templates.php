<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionTemplates\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\CustomOptions;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Ui\Component\Container;
use Magento\Ui\Component\Form\Element\Checkbox;
use Magento\Ui\Component\Form\Element\Hidden;
use Magento\Ui\Component\Form\Element\DataType\Number;
use Magento\Ui\Component\Form\Element\DataType\Text;
use Magento\Ui\Component\Form\Element\Input;
use Magento\Ui\Component\Form\Field;
use MageWorx\OptionTemplates\Model\ResourceModel\Group\CollectionFactory as GroupCollectionFactory;
use MageWorx\OptionTemplates\Model\ResourceModel\Group as GroupResourceModel;
use MageWorx\OptionBase\Ui\DataProvider\Product\Form\Modifier\ModifierInterface;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use Magento\Framework\UrlInterface;

class Templates extends AbstractModifier implements ModifierInterface
{
    const KEY_TEMPLATE_NAME = 'template_name';
    const KEY_GROUP_ID      = 'group_id';

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \Magento\Catalog\Model\Locator\LocatorInterface
     */
    protected $locator;

    /**
     * @var ArrayManager
     */
    protected $arrayManager;

    /**
     * @var GroupCollectionFactory
     */
    protected $groupCollectionFactory;

    /**
     * @var GroupResourceModel
     */
    protected $groupResourceModel;

    /**
     * @var BaseHelper
     */
    protected $baseHelper;

    /**
     * @var array
     */
    protected $groupsOptionArray = [];

    /**
     * @var array
     */
    protected $productGroupIds = [];

    /**
     * @var array
     */
    protected $meta = [];

    /**
     * @param LocatorInterface $locator
     * @param ArrayManager $arrayManager
     * @param GroupCollectionFactory $groupCollectionFactory
     * @param GroupResourceModel $groupResourceModel
     * @param UrlInterface $urlBuilder
     * @param BaseHelper $baseHelper
     */
    public function __construct(
        LocatorInterface $locator,
        ArrayManager $arrayManager,
        GroupCollectionFactory $groupCollectionFactory,
        GroupResourceModel $groupResourceModel,
        BaseHelper $baseHelper,
        UrlInterface $urlBuilder
    ) {
        $this->locator = $locator;
        $this->arrayManager = $arrayManager;
        $this->groupCollectionFactory = $groupCollectionFactory;
        $this->groupResourceModel = $groupResourceModel;
        $this->urlBuilder = $urlBuilder;
        $this->baseHelper = $baseHelper;
    }

    /**
     * Get sort order of modifier to load modifiers in the right order
     *
     * @return int
     */
    public function getSortOrder()
    {
        return 20;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        $groupNames = [];
        foreach ($this->groupsOptionArray as $optionItem) {
            $groupNames[$optionItem['value']] = $optionItem['label'];
        }

        $productId = $this->locator->getProduct()->getId();
        $linkFieldId = $this->locator->getProduct()->getData($this->baseHelper->getLinkField());
        $productOptions = !empty($data[$productId]['product']['options'])
            ? $data[$productId]['product']['options']
            : [];
        $productOptionToGroupRelations = $this->groupResourceModel->getProductOptionToGroupRelations($linkFieldId);

        foreach ($productOptions as $index => $productOption) {
            if ($this->isNotTemplateOption($productOption, $productOptionToGroupRelations, $groupNames)) {
                $productOptions[$index][self::KEY_TEMPLATE_NAME] = '';
                $productOptions[$index][self::KEY_GROUP_ID] = null;
                continue;
            }
            $productOptionGroupId = $productOptionToGroupRelations[$productOption['option_id']];
            $productOptions[$index][self::KEY_TEMPLATE_NAME] = $groupNames[$productOptionGroupId];
            $productOptions[$index][self::KEY_GROUP_ID] = $productOptionGroupId;
        }

        $data[$productId]['product']['options'] = $productOptions;
        return $data;
    }

    /**
     * Check if product option is not from template
     *
     * @param array $productOption
     * @param array $productOptionToGroupRelations
     * @param array $groupNames
     * @return bool
     */
    protected function isNotTemplateOption($productOption, $productOptionToGroupRelations, $groupNames)
    {
        return empty($productOption['group_option_id'])
            || empty($productOption['option_id'])
            || empty($productOptionToGroupRelations[$productOption['option_id']])
            || empty($groupNames[$productOptionToGroupRelations[$productOption['option_id']]]);
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        $groupCustomOptionsName = CustomOptions::GROUP_CUSTOM_OPTIONS_NAME;

        $this->meta = $meta;

        $product = $this->locator->getProduct();
        $productId = $product->getId();
        /** @var \MageWorx\OptionTemplates\Model\ResourceModel\Group\Collection $collection */
        $collection = $this->groupCollectionFactory->create();
        $this->groupsOptionArray = $collection->toOptionArray();
        $this->productGroupIds = $productId ? $collection->addProductFilter($productId)->getAllIds() : [];


        // Add fields to the template features container
        $templateFeaturesFields = $this->getTemplateFeaturesFieldsConfig();
        $this->meta[$groupCustomOptionsName]['children'] = array_replace_recursive(
            $this->meta[$groupCustomOptionsName]['children'],
            $templateFeaturesFields
        );

        $this->addTemplateNameToOptions();

        return $this->meta;
    }

    protected function getTemplateFeaturesFieldsConfig()
    {
        $children =  [];
        $children['option_groups'] = $this->addTemplates(10);
        $children['keep_options_on_unlink'] = $this->addKeepOptionsOnUnlink(20);

        $fields = [
            'template_features_container' => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'componentType' => Container::NAME,
                            'formElement' => Container::NAME,
                            'component' => 'Magento_Ui/js/form/components/group',
                            'breakLine' => false,
                            'showLabel' => false,
                            'additionalClasses' =>
                                'admin__field-control admin__control-grouped admin__field-group-columns',
                            'sortOrder' => 5,
                        ],
                    ],
                ],
                'children' => $children
            ],
        ];

        return $fields;
    }

    protected function addKeepOptionsOnUnlink($sortOrder)
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => __('Keep Options on Unlink'),
                        'componentType' => Field::NAME,
                        'formElement' => Checkbox::NAME,
                        'value' => 0,
                        'dataType' => Number::NAME,
                        'prefer' => 'toggle',
                        'valueMap' => [
                            'true' => 1,
                            'false' => 0,
                        ],
                        'fit' => true,
                        'sortOrder' => $sortOrder,
                    ],
                ],
            ],
        ];
    }

    protected function addTemplates($sortOrder)
    {
        $options[] = [
            'value' => 'none',
            'label' => 'None'
        ];

        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'dataType' => Text::NAME,
                        'formElement' => \Magento\Ui\Component\Form\Element\MultiSelect::NAME,
                        'componentType' => \Magento\Ui\Component\Form\Field::NAME,
                        'dataScope' => 'option_groups',
                        'additionalClasses' => 'mageworx-width-percent-20',
                        'id' => 'mageworx_product_group',
                        'label' => __('MageWorx Option Templates'),
                        'options' => array_merge($options, $this->groupsOptionArray),
                        'value' => $this->productGroupIds,
                        'visible' => true,
                        'disabled' => false,
                        'sortOrder' => $sortOrder,
                    ],
                ],
            ],
            'children' => [],
        ];
    }

    protected function addTemplateNameToOptions()
    {
        $groupCustomOptionsName = CustomOptions::GROUP_CUSTOM_OPTIONS_NAME;
        $optionContainerName = CustomOptions::CONTAINER_OPTION;
        $commonOptionContainerName = CustomOptions::CONTAINER_COMMON_NAME;

        // Add fields to the option
        $optionFields = $this->getTemplateRelationsFieldConfig();
        $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
        [$optionContainerName]['children'][$commonOptionContainerName]['children'] = array_replace_recursive(
            $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
            [$optionContainerName]['children'][$commonOptionContainerName]['children'],
            $optionFields
        );
    }

    /**
     * The custom option fields config
     *
     * @return array
     */
    protected function getTemplateRelationsFieldConfig()
    {
        $fields['template_relation'] = $this->getTemplateInputConfig(25);
        $fields['group_id'] = $this->getHiddenGroupIdConfig(26);
        return $fields;
    }

    /**
     * Template Relation field config
     *
     * @param $sortOrder
     * @return array
     */
    protected function getTemplateInputConfig($sortOrder)
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => __('Template'),
                        'component' => 'MageWorx_OptionTemplates/js/component/template-dependent-input',
                        'componentType' => Field::NAME,
                        'formElement' => Input::NAME,
                        'dataScope' => static::KEY_TEMPLATE_NAME,
                        'dataType' => Text::NAME,
                        'disabled' => true,
                        'sortOrder' => $sortOrder
                    ],
                ],
            ],
        ];
    }

    /**
     * Get group id
     * @param $sortOrder
     * @return array
     */
    protected function getHiddenGroupIdConfig($sortOrder)
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => __('Group ID'),
                        'componentType' => Field::NAME,
                        'formElement' => Hidden::NAME,
                        'dataScope' => static::KEY_GROUP_ID,
                        'dataType' => Text::NAME,
                        'visible' => false,
                        'displayArea' => 'insideGroup',
                        'sortOrder' => $sortOrder,
                    ],
                ],
            ],
        ];
    }

    /**
     * Check is current modifier for the product only
     *
     * @return bool
     */
    public function isProductScopeOnly()
    {
        return true;
    }
}
