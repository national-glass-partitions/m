<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionFeatures\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\CustomOptions;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Container;
use Magento\Ui\Component\Form\Element\Select;
use Magento\Ui\Component\Form\Element\Checkbox;
use Magento\Ui\Component\Form\Element\DataType\Number;
use Magento\Ui\Component\Form\Element\DataType\Text;
use Magento\Ui\Component\Form\Field;
use MageWorx\OptionBase\Ui\DataProvider\Product\Form\Modifier\ModifierInterface;
use MageWorx\OptionFeatures\Helper\Data as Helper;
use Magento\Ui\Component\Form\Element\Hidden;
use Magento\Ui\Component\Modal;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\Request\Http;
use MageWorx\OptionFeatures\Model\Config\Source\ShareableLinkMode as SourceConfig;
use MageWorx\OptionBase\Helper\Data as HelperBase;

class Features extends AbstractModifier implements ModifierInterface
{
    const OPTION_VALUE_IMAGES_LAYOUT      = 'option_value_image_index';
    const OPTION_VALUE_IMAGES_MODAL_INDEX = 'option_value_images_modal';
    const OPTION_VALUE_IMAGES_FORM        = 'option_value_images_form';
    const OPTION_VALUE_IMAGES_DATA        = 'images_data';

    const MODAL_CONTENT  = 'content';
    const MODAL_FIELDSET = 'fieldset';

    protected UrlInterface $urlBuilder;
    protected ArrayManager $arrayManager;
    protected StoreManagerInterface $storeManager;
    protected LocatorInterface $locator;
    protected SourceConfig $sourceConfig;
    protected Helper $helper;
    /**
     * @var \MageWorx\OptionVisibility\Helper\Data
     */
    protected $helperBase;
    protected Http $request;
    protected array $meta = [];
    protected string $form = 'product_form';

    /**
     * Features constructor.
     *
     * @param ArrayManager $arrayManager
     * @param StoreManagerInterface $storeManager
     * @param LocatorInterface $locator
     * @param Helper $helper
     * @param HelperBase $helperBase
     * @param Http $request
     * @param SourceConfig $sourceConfig
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        ArrayManager $arrayManager,
        StoreManagerInterface $storeManager,
        LocatorInterface $locator,
        Helper $helper,
        HelperBase $helperBase,
        Http $request,
        SourceConfig $sourceConfig,
        UrlInterface $urlBuilder
    ) {
        $this->arrayManager = $arrayManager;
        $this->storeManager = $storeManager;
        $this->locator      = $locator;
        $this->helper       = $helper;
        $this->helperBase   = $helperBase;
        $this->request      = $request;
        $this->sourceConfig = $sourceConfig;
        $this->urlBuilder   = $urlBuilder;
    }

    /**
     * Get sort order of modifier to load modifiers in the right order
     *
     * @return int
     */
    public function getSortOrder()
    {
        return 50;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $this->locator->getProduct();

        if (!$product || !$product->getId()) {
            $storeId                         = $this->storeManager->getStore()->getId();
            $isAbsolutePriceEnabledByDefault = $this->helper->isAbsolutePriceEnabledByDefault($storeId);
            $key                             = 'product';
            if (is_array($data) && isset($data['']['mageworx_optiontemplates_group'])) {
                $key = 'mageworx_optiontemplates_group';
            }
            $data[''][$key][Helper::KEY_ABSOLUTE_PRICE] = $isAbsolutePriceEnabledByDefault ? '1' : '0';
            return $data;
        }

        return array_replace_recursive(
            $data,
            [
                $product->getId() => [
                    static::DATA_SOURCE_DEFAULT => [
                        Helper::KEY_ABSOLUTE_COST                 => $product->getData(Helper::KEY_ABSOLUTE_COST),
                        Helper::KEY_ABSOLUTE_WEIGHT               => $product->getData(Helper::KEY_ABSOLUTE_WEIGHT),
                        Helper::KEY_ABSOLUTE_PRICE                => $product->getData(Helper::KEY_ABSOLUTE_PRICE),
                        Helper::KEY_HIDE_ADDITIONAL_PRODUCT_PRICE => $product->getData(
                            Helper::KEY_HIDE_ADDITIONAL_PRODUCT_PRICE
                        ),
                        Helper::KEY_SHAREABLE_LINK                => $product->getData(Helper::KEY_SHAREABLE_LINK)
                    ],
                ],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        $this->meta = $meta;

        if ($this->request->getRouteName() == 'mageworx_optiontemplates') {
            $this->form = 'mageworx_optiontemplates_group_form';
        }

        $this->addFeaturesFields();

        $this->addImageModal();
        $this->addImagesButton();

        return $this->meta;
    }

    /**
     * Adds features fields to the meta-data
     */
    protected function addFeaturesFields()
    {
        $groupCustomOptionsName    = CustomOptions::GROUP_CUSTOM_OPTIONS_NAME;
        $optionContainerName       = CustomOptions::CONTAINER_OPTION;
        $commonOptionContainerName = CustomOptions::CONTAINER_COMMON_NAME;

        // Add fields to the values
        $valueFeaturesFields                                                           = $this->getValueFeaturesFieldsConfig(
        );
        $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
        [$optionContainerName]['children']['values']['children']['record']['children'] = array_replace_recursive(
            $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
            [$optionContainerName]['children']['values']['children']['record']['children'],
            $valueFeaturesFields
        );

        // Add fields to the option
        $optionFeaturesFields                                                      = $this->getOptionFeaturesFieldsConfig(
        );
        $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
        [$optionContainerName]['children'][$commonOptionContainerName]['children'] = array_replace_recursive(
            $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
            [$optionContainerName]['children'][$commonOptionContainerName]['children'],
            $optionFeaturesFields
        );

        // Add fields to the options container
        $productFeaturesFields                           = $this->getProductFeaturesFieldsConfig();
        $this->meta[$groupCustomOptionsName]['children'] = array_replace_recursive(
            $this->meta[$groupCustomOptionsName]['children'],
            $productFeaturesFields
        );
    }

    /**
     * The custom option value fields config
     *
     * @return array
     */
    protected function getValueFeaturesFieldsConfig()
    {
        $fields                         = [];
        $fields[Helper::KEY_IS_DEFAULT] = $this->getIsDefaultConfig(148);
        return $fields;
    }

    /**
     * Is default field config
     *
     * @param $sortOrder
     * @return array
     */
    protected function getIsDefaultConfig($sortOrder)
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label'         => __('Is Default'),
                        'componentType' => Field::NAME,
                        'component'     => 'MageWorx_OptionFeatures/js/element/is-default',
                        'formElement'   => Checkbox::NAME,
                        'dataScope'     => Helper::KEY_IS_DEFAULT,
                        'dataType'      => Number::NAME,
                        'prefer'        => 'toggle',
                        'valueMap'      => [
                            'true'  => Helper::IS_DEFAULT_TRUE,
                            'false' => Helper::IS_DEFAULT_FALSE,
                        ],
                        'fit'           => true,
                        'sortOrder'     => $sortOrder
                    ],
                ],
            ],
        ];
    }

    /**
     * The custom option fields config
     *
     * @return array
     */
    protected function getOptionFeaturesFieldsConfig()
    {
        $fields = [];

        if ($this->helper->isOneTimeEnabled()) {
            $fields[Helper::KEY_ONE_TIME] = $this->getOneTimeConfig(60);
        }

        if ($this->helper->isQtyInputEnabled()) {
            $fields[Helper::KEY_QTY_INPUT] = $this->getQtyInputConfig(63);
        }

        $fields[Helper::KEY_IS_HIDDEN] = $this->getIsHiddenConfig(66);

        return $fields;
    }

    /**
     * Is Hidden Option field config
     *
     * @param $sortOrder
     * @return array
     */
    protected function getIsHiddenConfig($sortOrder)
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label'         => __('Is Hidden'),
                        'componentType' => Field::NAME,
                        'component'     => 'MageWorx_OptionFeatures/js/element/is-hidden',
                        'formElement'   => Checkbox::NAME,
                        'dataScope'     => Helper::KEY_IS_HIDDEN,
                        'dataType'      => Text::NAME,
                        'sortOrder'     => $sortOrder,
                        'prefer'        => 'toggle',
                        'valueMap'      => [
                            'true'  => '1',
                            'false' => '0',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Enable qty input (for option) field config
     *
     * @param $sortOrder
     * @return array
     */
    protected function getQtyInputConfig($sortOrder)
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label'         => __('Qty Input'),
                        'componentType' => Field::NAME,
                        'component'     => 'MageWorx_OptionFeatures/js/element/option-filtered-qty-input-checkbox',
                        'formElement'   => Checkbox::NAME,
                        'dataScope'     => Helper::KEY_QTY_INPUT,
                        'dataType'      => Text::NAME,
                        'sortOrder'     => $sortOrder,
                        'prefer'        => 'toggle',
                        'valueMap'      => [
                            'true'  => Helper::QTY_INPUT_TRUE,
                            'false' => Helper::QTY_INPUT_FALSE,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Is One Time Option field config
     *
     * @param $sortOrder
     * @return array
     */
    protected function getOneTimeConfig($sortOrder)
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label'         => __('One Time'),
                        'componentType' => Field::NAME,
                        'formElement'   => Checkbox::NAME,
                        'dataScope'     => Helper::KEY_ONE_TIME,
                        'dataType'      => Text::NAME,
                        'sortOrder'     => $sortOrder,
                        'prefer'        => 'toggle',
                        'tooltip'       => [
                            'description' => __(
                                    'Due to Magento calculations, the one-time option price is divided by the added quantity to get the final price.'
                                ) .
                                ' ' .
                                __('Note: the final price may possibly differ by 1-2 cents.')
                        ],
                        'valueMap'      => [
                            'true'  => Helper::ONE_TIME_TRUE,
                            'false' => Helper::ONE_TIME_FALSE,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * The product based features config (see `catalog_product_entity` table)
     *
     * @return array
     */
    protected function getProductFeaturesFieldsConfig()
    {
        $children = [];
        if ($this->helper->isAbsoluteCostEnabled()) {
            $children[Helper::KEY_ABSOLUTE_COST] = $this->getAbsoluteCostConfig(5);
        }
        if ($this->helper->isAbsoluteWeightEnabled()) {
            $children[Helper::KEY_ABSOLUTE_WEIGHT] = $this->getAbsoluteWeightConfig(7);
        }
        if ($this->helper->isAbsolutePriceEnabled()) {
            $children[Helper::KEY_ABSOLUTE_PRICE] = $this->getAbsolutePriceConfig(9);
        }
        if ($this->helper->isEnabledAdditionalProductPriceField()) {
            $children[Helper::KEY_HIDE_ADDITIONAL_PRODUCT_PRICE] = $this->getHideAdditionalProductPriceConfig(11);
        }
        if ($this->helper->isEnabledShareableLink()) {
            $children[Helper::KEY_SHAREABLE_LINK] = $this->getShareableLinkFieldConfig(15);
        }

        return [
            'global_config_container' => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'componentType'     => Container::NAME,
                            'formElement'       => Container::NAME,
                            'component'         => 'Magento_Ui/js/form/components/group',
                            'breakLine'         => false,
                            'showLabel'         => false,
                            'additionalClasses' =>
                                'admin__field-control admin__control-grouped admin__field-group-columns',
                            'sortOrder'         => 10,
                        ],
                    ],
                ],
                'children'  => $children
            ],
        ];
    }

    /**
     * Absolute Cost field config
     *
     * @param $sortOrder
     * @return array
     */
    protected function getAbsoluteCostConfig($sortOrder)
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label'         => __('Absolute Cost'),
                        'componentType' => Field::NAME,
                        'formElement'   => Checkbox::NAME,
                        'dataScope'     => Helper::KEY_ABSOLUTE_COST,
                        'dataType'      => Number::NAME,
                        'prefer'        => 'toggle',
                        'valueMap'      => [
                            'true'  => Helper::ABSOLUTE_COST_TRUE,
                            'false' => Helper::ABSOLUTE_COST_FALSE,
                        ],
                        'fit'           => true,
                        'sortOrder'     => $sortOrder,
                    ],
                ],
            ],
        ];
    }

    /**
     * Absolute Weight field config
     *
     * @param $sortOrder
     * @return array
     */
    protected function getAbsoluteWeightConfig($sortOrder)
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label'         => __('Absolute Weight'),
                        'componentType' => Field::NAME,
                        'formElement'   => Checkbox::NAME,
                        'dataScope'     => Helper::KEY_ABSOLUTE_WEIGHT,
                        'dataType'      => Number::NAME,
                        'prefer'        => 'toggle',
                        'valueMap'      => [
                            'true'  => Helper::ABSOLUTE_WEIGHT_TRUE,
                            'false' => Helper::ABSOLUTE_WEIGHT_FALSE,
                        ],
                        'fit'           => true,
                        'sortOrder'     => $sortOrder,
                    ],
                ],
            ],
        ];
    }

    /**
     * Absolute Price field config
     *
     * @param $sortOrder
     * @return array
     */
    protected function getAbsolutePriceConfig($sortOrder)
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label'         => __('Absolute Price'),
                        'componentType' => Field::NAME,
                        'formElement'   => Checkbox::NAME,
                        'dataScope'     => Helper::KEY_ABSOLUTE_PRICE,
                        'dataType'      => Number::NAME,
                        'prefer'        => 'toggle',
                        'valueMap'      => [
                            'true'  => Helper::ABSOLUTE_PRICE_TRUE,
                            'false' => Helper::ABSOLUTE_PRICE_FALSE,
                        ],
                        'fit'           => true,
                        'sortOrder'     => $sortOrder,
                    ],
                ],
            ],
        ];
    }

    /**
     * Additional product price field config
     *
     * @param $sortOrder
     * @return array
     */
    protected function getHideAdditionalProductPriceConfig($sortOrder)
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label'         => __('Hide Additional Product Price Field'),
                        'componentType' => Field::NAME,
                        'formElement'   => Checkbox::NAME,
                        'dataScope'     => Helper::KEY_HIDE_ADDITIONAL_PRODUCT_PRICE,
                        'dataType'      => Number::NAME,
                        'prefer'        => 'toggle',
                        'valueMap'      => [
                            'true'  => Helper::ABSOLUTE_PRICE_TRUE,
                            'false' => Helper::ABSOLUTE_PRICE_FALSE
                        ],
                        'fit'           => true,
                        'sortOrder'     => $sortOrder,
                    ],
                ],
            ],
        ];
    }

    /**
     * Get Shareable Link Field config for product
     *
     * @param $sortOrder
     * @return array
     */
    protected function getShareableLinkFieldConfig($sortOrder)
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label'         => __('Shareable Link'),
                        'componentType' => Field::NAME,
                        'component'     => 'Magento_Ui/js/form/element/select',
                        'formElement'   => Select::NAME,
                        'dataScope'     => Helper::KEY_SHAREABLE_LINK,
                        'dataType'      => Text::NAME,
                        'disableLabel'  => true,
                        'multiple'      => false,
                        'options'       => $this->sourceConfig->getOptions(),
                        'sortOrder'     => $sortOrder
                    ],
                ],
            ],
        ];
    }

    protected function addImageModal()
    {
        $this->meta = array_merge_recursive(
            $this->meta,
            [
                static::OPTION_VALUE_IMAGES_MODAL_INDEX => $this->getModalConfig(),
            ]
        );
    }

    protected function getModalConfig()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'isTemplate'    => false,
                        'componentType' => Modal::NAME,
                        'provider'      => static::FORM_NAME . '.' . static::FORM_NAME . '_data_source',
                        'options'       => [
                            'title' => __('Manage Images'),
                        ],
                        'imports'       => [
                            'state' => '!index=' . static::MODAL_CONTENT . ':responseStatus',
                        ],
                    ],
                ],
            ],
            'children'  => [
                static::MODAL_CONTENT => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'autoRender'       => false,
                                'label'            => '',
                                'componentType'    => 'container',
                                'component'        => 'MageWorx_OptionFeatures/js/component/images-insert-form',
                                'update_url'       => $this->urlBuilder->getUrl('mui/index/render'),
                                'render_url'       => $this->urlBuilder->getUrl(
                                    'mui/index/render_handle',
                                    [
                                        'handle'   => static::OPTION_VALUE_IMAGES_LAYOUT,
                                        'store'    => $this->locator->getProduct()->getStoreId(),
                                        'buttons'  => 1,
                                        'formName' => $this->form,
                                    ]
                                ),
                                'ns'               => static::OPTION_VALUE_IMAGES_FORM,
                                'externalProvider' => static::OPTION_VALUE_IMAGES_FORM . '.'
                                    . static::OPTION_VALUE_IMAGES_FORM . '_data_source',
                                'toolbarContainer' => '${ $.parentName }',
                                'formSubmitType'   => 'ajax',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function addImagesButton()
    {
        $groupCustomOptionsName = CustomOptions::GROUP_CUSTOM_OPTIONS_NAME;
        $optionContainerName    = CustomOptions::CONTAINER_OPTION;

        $imagesButtonFieldConfig                                                       = $this->getImagesButtonFieldConfig(
        );
        $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
        [$optionContainerName]['children']['values']['children']['record']['children'] = array_replace_recursive(
            $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
            [$optionContainerName]['children']['values']['children']['record']['children'],
            $imagesButtonFieldConfig
        );

        $imagesHiddenFieldConfig                                                       = $this->getImagesHiddenFieldConfig(
        );
        $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
        [$optionContainerName]['children']['values']['children']['record']['children'] = array_replace_recursive(
            $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
            [$optionContainerName]['children']['values']['children']['record']['children'],
            $imagesHiddenFieldConfig
        );
    }

    /**
     * The custom option value fields config
     *
     * @return array
     */
    protected function getImagesButtonFieldConfig()
    {
        $fields                = [];
        $fields['images_link'] = $this->getImagesButtonConfig(201);

        return $fields;
    }

    /**
     * Upload image field config
     *
     * @param $sortOrder
     * @return array
     */
    protected function getImagesButtonConfig($sortOrder)
    {
        $params = [
            'provider'     => '${ $.provider }',
            'dataScope'    => '${ $.dataScope }',
            'loadImageUrl' => $this->urlBuilder->getUrl(
                'mageworx_optionfeatures/form_image/load'
            ),
            'formName'     => $this->form,
            'buttonName'   => '${ $.name }'
        ];

        if ($this->helperBase->checkModuleVersion('104.0.0')) {
            $params['__disableTmpl'] = [
                'provider'   => false,
                'dataScope'  => false,
                'buttonName' => false
            ];
        }

        $mageworxAttributes = [
            static::OPTION_VALUE_IMAGES_DATA => '${ $.dataScope }' . '.' . static::OPTION_VALUE_IMAGES_DATA
        ];

        if ($this->helperBase->checkModuleVersion('104.0.0')) {
            $mageworxAttributes['__disableTmpl'] = [
                static::OPTION_VALUE_IMAGES_DATA => false
            ];
        }

        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'displayAsLink'      => false,
                        'formElement'        => Container::NAME,
                        'componentType'      => Container::NAME,
                        'component'          => 'MageWorx_OptionBase/component/button',
                        'elementTmpl'        => 'MageWorx_OptionBase/button',
                        'buttonClasses'      => 'mageworx-icon images',
                        'sortOrder'          => $sortOrder,
                        'tooltipTpl'         => 'MageWorx_OptionBase/tooltip',
                        'tooltip'            => [
                            'description' => __('Images')
                        ],
                        'mageworxAttributes' => $mageworxAttributes,
                        'actions'            => [
                            [
                                'targetName' => 'ns=' . $this->form . ', index='
                                    . static::OPTION_VALUE_IMAGES_MODAL_INDEX,
                                'actionName' => 'openModal',
                            ],
                            [
                                'targetName' =>
                                    $this->form . '.' . $this->form . '.'
                                    . static::OPTION_VALUE_IMAGES_MODAL_INDEX . '.' . static::MODAL_CONTENT,
                                'actionName' => 'render',
                            ],
                            [
                                'targetName' =>
                                    $this->form . '.' . $this->form . '.'
                                    . static::OPTION_VALUE_IMAGES_MODAL_INDEX . '.' . static::MODAL_CONTENT,
                                'actionName' => 'loadImagesData',
                                'params'     => [
                                    $params,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * The custom option value fields config
     *
     * @return array
     */
    protected function getImagesHiddenFieldConfig()
    {
        $fields                                   = [];
        $fields[static::OPTION_VALUE_IMAGES_DATA] = $this->getImagesDataConfig(171);

        return $fields;
    }

    /**
     * Upload image field config
     *
     * @param $sortOrder
     * @return array
     */
    protected function getImagesDataConfig($sortOrder)
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => Field::NAME,
                        'formElement'   => Hidden::NAME,
                        'sortOrder'     => $sortOrder,
                        'visible'       => false,
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
        return false;
    }
}
