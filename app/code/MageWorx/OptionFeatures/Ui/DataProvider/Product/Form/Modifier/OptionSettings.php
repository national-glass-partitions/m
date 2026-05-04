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
use Magento\Ui\Component\Form\Element\Checkbox;
use Magento\Ui\Component\Form\Element\DataType\Number;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\Component\Form\Fieldset;
use Magento\Ui\Component\Form\Element\DataType\Text;
use Magento\Ui\Component\Form\Element\Input;
use MageWorx\OptionBase\Ui\DataProvider\Product\Form\Modifier\ModifierInterface;
use MageWorx\OptionFeatures\Helper\Data as Helper;
use Magento\Ui\Component\Form\Element\Select;
use Magento\Ui\Component\Modal;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\Request\Http;
use MageWorx\OptionBase\Helper\Data as BaseHelper;

class OptionSettings extends AbstractModifier implements ModifierInterface
{
    const OPTION_SETTINGS_MODAL_INDEX = 'option_settings_modal';
    const OPTION_SETTINGS_BUTTON_NAME = 'button_option_settings';
    const OPTION_SETTINGS             = 'option_settings';

    const MODAL_CONTENT  = 'content';
    const MODAL_FIELDSET = 'fieldset';

    const CONTAINER_HEADER_NAME = 'header';

    protected UrlInterface $urlBuilder;
    protected ArrayManager $arrayManager;
    protected StoreManagerInterface $storeManager;
    protected LocatorInterface $locator;
    protected Helper $helper;
    protected Http $request;
    protected array $meta = [];
    protected string $form = 'product_form';
    protected BaseHelper $baseHelper;

    /**
     * OptionSettings constructor.
     *
     * @param ArrayManager $arrayManager
     * @param StoreManagerInterface $storeManager
     * @param LocatorInterface $locator
     * @param Helper $helper
     * @param Http $request
     * @param UrlInterface $urlBuilder
     * @param BaseHelper $baseHelper
     */
    public function __construct(
        ArrayManager $arrayManager,
        StoreManagerInterface $storeManager,
        LocatorInterface $locator,
        Helper $helper,
        Http $request,
        UrlInterface $urlBuilder,
        BaseHelper $baseHelper
    ) {
        $this->arrayManager = $arrayManager;
        $this->storeManager = $storeManager;
        $this->locator      = $locator;
        $this->helper       = $helper;
        $this->request      = $request;
        $this->urlBuilder   = $urlBuilder;
        $this->baseHelper   = $baseHelper;
    }

    /**
     * Get sort order of modifier to load modifiers in the right order
     *
     * @return int
     */
    public function getSortOrder()
    {
        return 55;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        return $data;
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

        $this->addOptionSettingsModal();
        $this->addOptionSettingsButton();

        return $this->meta;
    }

    /**
     * Show option settings button
     */
    protected function addOptionSettingsButton()
    {
        $groupCustomOptionsName    = CustomOptions::GROUP_CUSTOM_OPTIONS_NAME;
        $optionContainerName       = CustomOptions::CONTAINER_OPTION;
        $commonOptionContainerName = CustomOptions::CONTAINER_COMMON_NAME;

        $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
        [$optionContainerName]['children'][$commonOptionContainerName]['children'] = array_replace_recursive(
            $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
            [$optionContainerName]['children'][$commonOptionContainerName]['children'],
            $this->getOptionSettingsButtonConfig(124, true)
        );
    }

    /**
     * Get option settings button config
     *
     * @param int $sortOrder
     * @param bool $additionalForGroup
     * @return array
     */
    protected function getOptionSettingsButtonConfig($sortOrder, $additionalForGroup = false)
    {
        $params = [
            'provider'                           => '${ $.provider }',
            'dataScope'                          => '${ $.dataScope }',
            'formName'                           => $this->form,
            'buttonName'                         => '${ $.name }',
            'isEnabledHideProductPageValuePrice' => $this->helper->isEnabledHideProductPageValuePrice(),
            'pathHideValuePrice'                 => Helper::KEY_HIDE_PRODUCT_PAGE_VALUE_PRICE
        ];

        if ($this->baseHelper->checkModuleVersion('104.0.0')) {
            $params['__disableTmpl'] = [
                'provider'  => false,
                'dataScope' => false,
                'name'      => false
            ];
        }

        return [
            static::OPTION_SETTINGS_BUTTON_NAME => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'labelVisible'       => true,
                            'label'              => ' ',
                            'formElement'        => Container::NAME,
                            'componentType'      => Container::NAME,
                            'component'          => 'MageWorx_OptionBase/component/button',
                            'additionalForGroup' => $additionalForGroup,
                            'additionalClasses'  => 'mageworx-icon-additional-container',
                            'displayArea'        => 'insideGroup',
                            'template'           => 'ui/form/components/button/container',
                            'elementTmpl'        => 'MageWorx_OptionBase/button',
                            'buttonClasses'      => 'mageworx-icon settings',
                            'tooltipTpl'         => 'MageWorx_OptionBase/tooltip',
                            'tooltip'            => [
                                'description' => __('Option Settings')
                            ],
                            'mageworxAttributes' => $this->getEnabledAttributes(),
                            'displayAsLink'      => false,
                            'fit'                => true,
                            'sortOrder'          => $sortOrder,
                            'actions'            => [
                                [
                                    'targetName' => 'ns=' . $this->form . ', index='
                                        . static::OPTION_SETTINGS_MODAL_INDEX,
                                    'actionName' => 'openModal',
                                ],
                                [
                                    'targetName' => 'ns=' . $this->form . ', index='
                                        . static::OPTION_SETTINGS_MODAL_INDEX,
                                    'actionName' => 'reloadModal',
                                    'params'     => [
                                        $params,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        ];
    }

    /**
     * Add modal window to manage option settings
     */
    protected function addOptionSettingsModal()
    {
        $this->meta = array_merge_recursive(
            $this->meta,
            [
                static::OPTION_SETTINGS_MODAL_INDEX => $this->getOptionSettingsModalConfig(),
            ]
        );
    }

    /**
     * Get option settings modal config
     */
    protected function getOptionSettingsModalConfig()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'isTemplate'    => false,
                        'component'     => 'MageWorx_OptionFeatures/js/component/modal-option-settings',
                        'componentType' => Modal::NAME,
                        'dataScope'     => static::OPTION_SETTINGS,
                        'provider'      => static::FORM_NAME . '.' . static::FORM_NAME . '_data_source',
                        'options'       => [
                            'title'   => __('Option Settings Management'),
                            'buttons' => [
                                [
                                    'text'    => __('Save & Close'),
                                    'class'   => 'action-primary',
                                    'actions' => [
                                        'save',
                                    ],
                                ],
                            ],
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
                                'autoRender'         => false,
                                'componentType'      => 'container',
                                'dataScope'          => 'data.product',
                                'externalProvider'   => 'data.product_data_source',
                                'ns'                 => static::FORM_NAME,
                                'behaviourType'      => 'edit',
                                'externalFilterMode' => true,
                                'currentProductId'   => $this->locator->getProduct()->getId(),
                            ],
                        ],
                    ],
                    'children'  => [
                        static::MODAL_FIELDSET => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'additionalClasses' => 'admin__fieldset-product-websites',
                                        'label'             => __('Option Settings For '),
                                        'collapsible'       => false,
                                        'componentType'     => Fieldset::NAME,
                                        'component'         => 'MageWorx_OptionBase/component/fieldset',
                                        'dataScope'         => 'custom_data',
                                        'disabled'          => false
                                    ],
                                ],
                            ],
                            'children'  => $this->getOptionSettingsFieldsConfig()
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
    protected function getOptionSettingsFieldsConfig(): array
    {
        $fields = [];

        $fields[Helper::KEY_OPTION_GALLERY_DISPLAY_MODE] =
            $this->getOptionGalleryDisplayModeFieldsConfig();
        $fields[Helper::KEY_OPTION_IMAGE_MODE]           = $this->getOptionImageModeFieldConfig();
        $fields[Helper::KEY_DIV_CLASS]                   = $this->getDivClassFieldConfig();
        $fields[Helper::KEY_SELECTION_LIMIT_FROM]        = $this->getSelectionLimitFromFieldConfig();
        $fields[Helper::KEY_SELECTION_LIMIT_TO]          = $this->getSelectionLimitToFieldConfig();

        if ($this->helper->isEnabledHideProductPageValuePrice()) {
            $fields[Helper::KEY_HIDE_PRODUCT_PAGE_VALUE_PRICE] = $this->getIsHideValuePriceConfig(70);
        }

        return $fields;
    }

    protected function getIsHideValuePriceConfig(int $sortOrder): array
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label'             => __('Hide Value Price On Product Page'),
                        'componentType'     => Field::NAME,
                        'formElement'       => Checkbox::NAME,
                        'dataScope'         => Helper::KEY_HIDE_PRODUCT_PAGE_VALUE_PRICE,
                        'dataType'          => Number::NAME,
                        'additionalClasses' => 'admin__field-small',
                        'prefer'            => 'toggle',
                        'valueMap'          => [
                            'true'  => Helper::IS_HIDE_VALUE_PRICE_ON_PRODUCT_PAGE_TRUE,
                            'false' => Helper::IS_HIDE_VALUE_PRICE_ON_PRODUCT_PAGE_FALSE,
                        ],
                        'fit'               => true,
                        'sortOrder'         => $sortOrder
                    ],
                ],
            ],
        ];
    }

    /**
     * Get config for the option gallery field
     *
     * @return array
     */
    protected function getOptionGalleryDisplayModeFieldsConfig()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label'         => __('Option Gallery Display Mode'),
                        'componentType' => Field::NAME,
                        'component'     => 'Magento_Ui/js/form/element/select',
                        'formElement'   => Select::NAME,
                        'dataScope'     => Helper::KEY_OPTION_GALLERY_DISPLAY_MODE,
                        'dataType'      => Number::NAME,
                        'sortOrder'     => 80,
                        'options'       => [
                            0 => [
                                'label' => __('Disabled'),
                                'value' => 0,
                            ],
                            1 => [
                                'label' => __('Beside Option'),
                                'value' => 1,
                            ],
                            2 => [
                                'label' => __('Once Selected'),
                                'value' => 2,
                            ],
                        ],
                        'disableLabel'  => true,
                        'multiple'      => false,
                    ],
                ],
            ],
        ];
    }

    /**
     * Get config for the Option Image Mode select
     *
     * @return array
     */
    protected function getOptionImageModeFieldConfig()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label'         => __('Image Mode'),
                        'componentType' => Field::NAME,
                        'component'     => 'Magento_Ui/js/form/element/select',
                        'formElement'   => Select::NAME,
                        'dataScope'     => Helper::KEY_OPTION_IMAGE_MODE,
                        'dataType'      => Number::NAME,
                        'sortOrder'     => 90,
                        'options'       => [
                            0 => [
                                'label' => __('Disabled'),
                                'value' => Helper::OPTION_IMAGE_MODE_DISABLED,
                            ],
                            1 => [
                                'label' => __('Replace'),
                                'value' => Helper::OPTION_IMAGE_MODE_REPLACE,
                            ],
                            2 => [
                                'label' => __('Overlay'),
                                'value' => Helper::OPTION_IMAGE_MODE_OVERLAY,
                            ],
                        ],
                        'disableLabel'  => true,
                        'multiple'      => false,
                    ],
                ],
            ],
        ];
    }

    /**
     * Get config for Div Class input
     */
    protected function getDivClassFieldConfig()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label'         => __('Div Class'),
                        'componentType' => Field::NAME,
                        'formElement'   => Input::NAME,
                        'dataScope'     => Helper::KEY_DIV_CLASS,
                        'dataType'      => Text::NAME,
                        'fit'           => true,
                        'sortOrder'     => 100,
                    ],
                ],
            ],
        ];
    }

    /**
     * Get config for Selection Limit From field
     */
    protected function getSelectionLimitFromFieldConfig()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label'             => __('Selection Limit From'),
                        'componentType'     => Field::NAME,
                        'formElement'       => Input::NAME,
                        'dataScope'         => Helper::KEY_SELECTION_LIMIT_FROM,
                        'dataType'          => Number::NAME,
                        'additionalClasses' => 'admin__field-small',
                        'fit'               => true,
                        'validation'        => [
                            'validate-number'          => true,
                            'validate-zero-or-greater' => true,
                        ],
                        'tooltip'           => [
                            'description' => __(
                                'These settings allow you to limit the number of values your customers can select within the particular option.'
                            )
                        ],
                        'sortOrder'         => 110,
                    ],
                ],
            ],
        ];
    }

    /**
     * Get config for Selection Limit To field
     */
    protected function getSelectionLimitToFieldConfig()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label'             => __('Selection Limit To'),
                        'componentType'     => Field::NAME,
                        'formElement'       => Input::NAME,
                        'dataScope'         => Helper::KEY_SELECTION_LIMIT_TO,
                        'dataType'          => Number::NAME,
                        'additionalClasses' => 'admin__field-small',
                        'fit'               => true,
                        'validation'        => [
                            'validate-number'          => true,
                            'validate-zero-or-greater' => true,
                        ],
                        'sortOrder'         => 120,
                    ],
                ],
            ],
        ];
    }

    /**
     * Get enabled attributes
     *
     * @return array
     */
    public function getEnabledAttributes()
    {
        $attributes = [];
        $dataScope  = '${ $.dataScope }';

        $attributes[Helper::KEY_OPTION_IMAGE_MODE]             = $dataScope . '.' . Helper::KEY_OPTION_IMAGE_MODE;
        $attributes[Helper::KEY_OPTION_GALLERY_DISPLAY_MODE]   = $dataScope . '.' . Helper::KEY_OPTION_GALLERY_DISPLAY_MODE;
        $attributes[Helper::KEY_DIV_CLASS]                     = $dataScope . '.' . Helper::KEY_DIV_CLASS;
        $attributes[Helper::KEY_SELECTION_LIMIT_FROM]          = $dataScope . '.' . Helper::KEY_SELECTION_LIMIT_FROM;
        $attributes[Helper::KEY_SELECTION_LIMIT_TO]            = $dataScope . '.' . Helper::KEY_SELECTION_LIMIT_TO;
        $attributes[Helper::KEY_HIDE_PRODUCT_PAGE_VALUE_PRICE] = $dataScope . '.' . Helper::KEY_HIDE_PRODUCT_PAGE_VALUE_PRICE;

        if ($this->baseHelper->checkModuleVersion('104.0.0')) {
            $attributes['__disableTmpl'] = [
                Helper::KEY_OPTION_IMAGE_MODE             => false,
                Helper::KEY_OPTION_GALLERY_DISPLAY_MODE   => false,
                Helper::KEY_DIV_CLASS                     => false,
                Helper::KEY_SELECTION_LIMIT_FROM          => false,
                Helper::KEY_SELECTION_LIMIT_TO            => false,
                Helper::KEY_HIDE_PRODUCT_PAGE_VALUE_PRICE => false,
            ];
        }

        return $attributes;
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
