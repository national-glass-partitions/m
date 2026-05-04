<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionFeatures\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\CustomOptions;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Container;
use Magento\Ui\Component\Form\Element\Input;
use Magento\Ui\Component\Form\Element\Select;
use Magento\Ui\Component\Form\Element\Checkbox;
use Magento\Ui\Component\Form\Element\DataType\Number;
use Magento\Ui\Component\Form\Element\DataType\Text;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\Component\Form\Fieldset;
use MageWorx\OptionBase\Ui\DataProvider\Product\Form\Modifier\ModifierInterface;
use MageWorx\OptionFeatures\Helper\Data as Helper;
use Magento\Ui\Component\Modal;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\Request\Http;
use MageWorx\OptionFeatures\Model\Config\Source\Product\Options\Weight as ProductOptionsWeight;
use MageWorx\OptionBase\Helper\Data as BaseHelper;

class ValueSettings extends AbstractModifier implements ModifierInterface
{
    const VALUE_SETTINGS_MODAL_INDEX = 'value_settings_modal';
    const VALUE_SETTINGS_BUTTON_NAME = 'button_value_settings';
    const VALUE_SETTINGS             = 'value_settings';

    const MODAL_CONTENT  = 'content';
    const MODAL_FIELDSET = 'fieldset';

    protected UrlInterface $urlBuilder;
    protected ArrayManager $arrayManager;
    protected StoreManagerInterface $storeManager;
    protected LocatorInterface $locator;
    protected Helper $helper;
    protected BaseHelper $baseHelper;
    protected Http $request;
    protected array $meta = [];
    protected ProductOptionsWeight $productOptionsWeight;
    protected string $form = 'product_form';

    /**
     * @param ArrayManager $arrayManager
     * @param StoreManagerInterface $storeManager
     * @param LocatorInterface $locator
     * @param Helper $helper
     * @param Http $request
     * @param UrlInterface $urlBuilder
     * @param ProductOptionsWeight $productOptionsWeight
     * @param BaseHelper $baseHelper
     */
    public function __construct(
        ArrayManager $arrayManager,
        StoreManagerInterface $storeManager,
        LocatorInterface $locator,
        Helper $helper,
        BaseHelper $baseHelper,
        Http $request,
        UrlInterface $urlBuilder,
        ProductOptionsWeight $productOptionsWeight
    ) {
        $this->arrayManager         = $arrayManager;
        $this->storeManager         = $storeManager;
        $this->locator              = $locator;
        $this->helper               = $helper;
        $this->baseHelper           = $baseHelper;
        $this->request              = $request;
        $this->urlBuilder           = $urlBuilder;
        $this->productOptionsWeight = $productOptionsWeight;
    }

    /**
     * Get sort order of modifier to load modifiers in the right order
     *
     * @return int
     */
    public function getSortOrder(): int
    {
        return 56;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data): array
    {
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta): array
    {
        $this->meta = $meta;

        if ($this->request->getRouteName() == 'mageworx_optiontemplates') {
            $this->form = 'mageworx_optiontemplates_group_form';
        }

        $this->addValueSettingsModal();
        $this->addValueSettingsButton();

        return $this->meta;
    }

    /**
     * Show option settings button
     */
    protected function addValueSettingsButton()
    {
        $groupCustomOptionsName = CustomOptions::GROUP_CUSTOM_OPTIONS_NAME;
        $optionContainerName    = CustomOptions::CONTAINER_OPTION;

        $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
        [$optionContainerName]['children']['values']['children']['record']['children'] = array_replace_recursive(
            $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
            ['container_option']['children']['values']['children']['record']['children'],
            $this->getValueSettingsButtonConfig(207)
        );
    }

    /**
     * Get value settings button config
     *
     * @param int $sortOrder
     * @return array
     */
    protected function getValueSettingsButtonConfig(int $sortOrder): array
    {
        $params = [
            'provider'                   => '${ $.provider }',
            'dataScope'                  => '${ $.dataScope }',
            'formName'                   => $this->form,
            'buttonName'                 => '${ $.name }',
            'isCostEnabled'              => $this->helper->isCostEnabled(),
            'isWeightEnabled'            => $this->helper->isWeightEnabled(),
            'isLoadLinkedProductEnabled' => $this->helper->isLoadLinkedProductEnabled(),
            'isNotConfigurableProduct'   => $this->locator->getProduct()->getTypeId() !== Configurable::TYPE_CODE,
            'pathLoadLinkedProduct'      => Helper::KEY_LOAD_LINKED_PRODUCT
        ];

        if ($this->baseHelper->checkModuleVersion('104.0.0')) {
            $params['__disableTmpl'] = [
                'provider'   => false,
                'dataScope'  => false,
                'buttonName' => false
            ];
        }

        return [
            static::VALUE_SETTINGS_BUTTON_NAME => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'labelVisible'       => true,
                            'label'              => ' ',
                            'formElement'        => Container::NAME,
                            'componentType'      => Container::NAME,
                            'component'          => 'MageWorx_OptionBase/component/button',
                            'elementTmpl'        => 'MageWorx_OptionBase/button',
                            'buttonClasses'      => 'mageworx-icon settings',
                            'tooltipTpl'         => 'MageWorx_OptionBase/tooltip',
                            'tooltip'            => [
                                'description' => __('Value Settings')
                            ],
                            'mageworxAttributes' => $this->getEnabledAttributes(),
                            'displayAsLink'      => false,
                            'fit'                => true,
                            'sortOrder'          => $sortOrder,
                            'actions'            => [
                                [
                                    'targetName' => 'ns=' . $this->form . ', index='
                                        . static::VALUE_SETTINGS_MODAL_INDEX,
                                    'actionName' => 'openModal',
                                ],
                                [
                                    'targetName' => 'ns=' . $this->form . ', index='
                                        . static::VALUE_SETTINGS_MODAL_INDEX,
                                    'actionName' => 'reloadModal',
                                    'params'     => [
                                        $params
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
     * Add modal window to manage value settings
     */
    protected function addValueSettingsModal()
    {
        $this->meta = array_merge_recursive(
            $this->meta,
            [
                static::VALUE_SETTINGS_MODAL_INDEX => $this->getValueSettingsModalConfig(),
            ]
        );
    }

    /**
     * Get value settings modal config
     */
    protected function getValueSettingsModalConfig(): array
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'isTemplate'    => false,
                        'component'     => 'MageWorx_OptionFeatures/js/component/modal-value-settings',
                        'componentType' => Modal::NAME,
                        'dataScope'     => static::VALUE_SETTINGS,
                        'provider'      => static::FORM_NAME . '.' . static::FORM_NAME . '_data_source',
                        'options'       => [
                            'title'   => __('Option Value Settings'),
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
                                        'label'             => __('Option Value Settings For '),
                                        'collapsible'       => false,
                                        'componentType'     => Fieldset::NAME,
                                        'component'         => 'MageWorx_OptionBase/component/fieldset',
                                        'dataScope'         => 'custom_data',
                                        'disabled'          => false
                                    ],
                                ],
                            ],
                            'children'  => $this->getValueSettingsFieldsConfig()
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
    protected function getValueSettingsFieldsConfig(): array
    {
        $fields = [];

        if ($this->helper->isCostEnabled()) {
            $fields[Helper::KEY_COST] = $this->getCostConfig(10);
        }
        if ($this->helper->isWeightEnabled()) {
            $fields[Helper::KEY_WEIGHT]      = $this->getWeightConfig(20);
            $fields[Helper::KEY_WEIGHT_TYPE] = $this->getWeightTypeConfig(30);
        }
        if ($this->locator->getProduct()->getTypeId() !== Configurable::TYPE_CODE) {
            $fields[Helper::KEY_QTY_MULTIPLIER] = $this->getQtyMultiplierConfig(40);
        }
        if ($this->helper->isLoadLinkedProductEnabled()) {
            $fields[Helper::KEY_LOAD_LINKED_PRODUCT] = $this->getIsLoadLinkedProductConfig(50);
        }

        return $fields;
    }

    protected function getIsLoadLinkedProductConfig(int $sortOrder): array
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label'             => __('Load Linked Product'),
                        'componentType'     => Field::NAME,
                        'formElement'       => Checkbox::NAME,
                        'dataScope'         => Helper::KEY_LOAD_LINKED_PRODUCT,
                        'dataType'          => Number::NAME,
                        'additionalClasses' => 'admin__field-small',
                        'prefer'            => 'toggle',
                        'valueMap'          => [
                            'true'  => Helper::IS_LOAD_LINKED_PRODUCT_TRUE,
                            'false' => Helper::IS_LOAD_LINKED_PRODUCT_FALSE,
                        ],
                        'fit'               => true,
                        'sortOrder'         => $sortOrder,
                        'tooltip'           => [
                            'description' => __(
                                'If enabled, the linked product will be loaded upon
                            the value selection on the front-end. The value should be linked to existing product to enable
                            this feature.'
                            )
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Cost field config
     *
     * @param int $sortOrder
     * @return array
     */
    protected function getCostConfig(int $sortOrder): array
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label'             => __('Cost'),
                        'componentType'     => Field::NAME,
                        'formElement'       => Input::NAME,
                        'dataScope'         => Helper::KEY_COST,
                        'dataType'          => Number::NAME,
                        'additionalClasses' => 'admin__field-small',
                        'addbefore'         => $this->getBaseCurrencySymbol(),
                        'validation'        => [
                            'validate-number'          => true,
                            'validate-zero-or-greater' => true,
                        ],
                        'sortOrder'         => $sortOrder,
                    ],
                ],
            ],
        ];
    }

    /**
     * Qty Multiplier field config
     *
     * @param int $sortOrder
     * @return array
     */
    protected function getQtyMultiplierConfig(int $sortOrder): array
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label'             => __('Qty Multiplier'),
                        'componentType'     => Field::NAME,
                        'formElement'       => Input::NAME,
                        'dataScope'         => Helper::KEY_QTY_MULTIPLIER,
                        'dataType'          => Number::NAME,
                        'additionalClasses' => 'admin__field-small',
                        'validation'        => [
                            'validate-number'          => true,
                            'validate-zero-or-greater' => true,
                        ],
                        'tooltip'           => [
                            'description' => __(
                                    'This setting defines the number that will be deducted from the stock of the main product once the order is placed with the particular option value.'
                                ) .
                                ' ' .
                                __(
                                    'The Qty multiplier will be multiplied by the product Qty, specified manually in the Qty field on the front-end.'
                                ) .
                                ' ' .
                                __('Leave "0" to disable this feature.')
                        ],
                        'sortOrder'         => $sortOrder,
                    ],
                ],
            ],
        ];
    }

    /**
     * Get currency symbol
     *
     * @return string
     */
    protected function getBaseCurrencySymbol(): ?string
    {
        return $this->storeManager->getStore()->getBaseCurrency()->getCurrencySymbol();
    }

    /**
     * Get weight unit name
     *
     * @return mixed
     */
    protected function getWeightUnit()
    {
        try {
            $unit = $this->locator->getStore()->getConfig('general/locale/weight_unit');
        } catch (\Exception $e) {
            $unit = $this->storeManager->getStore()->getConfig('general/locale/weight_unit');
        }

        return $unit;
    }

    /**
     * Weight field config
     *
     * @param int $sortOrder
     * @return array
     */
    protected function getWeightConfig(int $sortOrder): array
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label'             => __('Weight'),
                        'componentType'     => Field::NAME,
                        'component'         => 'Magento_Catalog/js/components/custom-options-component',
                        'template'          => 'Magento_Catalog/form/field',
                        'formElement'       => Input::NAME,
                        'dataScope'         => Helper::KEY_WEIGHT,
                        'dataType'          => Number::NAME,
                        'validation'        => [
                            'validate-number'          => true,
                            'validate-zero-or-greater' => true,
                        ],
                        'sortOrder'         => $sortOrder,
                        'additionalClasses' => 'admin__field-small',
                        'addbefore'         => $this->getWeightUnit(),
                        'addbeforePool'     => $this->productOptionsWeight
                            ->prefixesToOptionArray($this->getWeightUnit()),
                        'imports'           => [
                            'disabled' => '!${$.provider}:' . self::DATA_SCOPE_PRODUCT
                                . '.product_has_weight:value',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Weight field config
     *
     * @param int $sortOrder
     * @return array
     */
    protected function getWeightTypeConfig(int $sortOrder): array
    {
        return
            [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'label'         => __('Weight Type'),
                            'component'     => 'MageWorx_OptionFeatures/js/component/custom-options-weight-type',
                            'componentType' => Field::NAME,
                            'formElement'   => Select::NAME,
                            'dataScope'     => Helper::KEY_WEIGHT_TYPE,
                            'dataType'      => Text::NAME,
                            'sortOrder'     => $sortOrder,
                            'options'       => $this->productOptionsWeight->toOptionArray(),
                            'imports'       => [
                                'weightIndex' => Helper::KEY_WEIGHT,
                            ],
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
    public function getEnabledAttributes(): array
    {
        $attributes = [];

        $attributes[Helper::KEY_COST]                = '${ $.dataScope }' . '.' . Helper::KEY_COST;
        $attributes[Helper::KEY_WEIGHT]              = '${ $.dataScope }' . '.' . Helper::KEY_WEIGHT;
        $attributes[Helper::KEY_LOAD_LINKED_PRODUCT] = '${ $.dataScope }' . '.' . Helper::KEY_LOAD_LINKED_PRODUCT;
        if ($this->locator->getProduct()->getTypeId() !== Configurable::TYPE_CODE) {
            $attributes[Helper::KEY_QTY_MULTIPLIER] = '${ $.dataScope }' . '.' . Helper::KEY_QTY_MULTIPLIER;
        }

        if ($this->baseHelper->checkModuleVersion('104.0.0')) {
            $attributes['__disableTmpl'] = [
                Helper::KEY_COST                => false,
                Helper::KEY_WEIGHT              => false,
                Helper::KEY_LOAD_LINKED_PRODUCT => false
            ];
        }

        if ($this->locator->getProduct()->getTypeId() !== Configurable::TYPE_CODE) {
            if ($this->baseHelper->checkModuleVersion('104.0.0')) {
                $attributes['__disableTmpl'][Helper::KEY_QTY_MULTIPLIER] = false;
            }
        }

        return $attributes;
    }

    /**
     * Check is current modifier for the product only
     *
     * @return bool
     */
    public function isProductScopeOnly(): bool
    {
        return false;
    }
}
