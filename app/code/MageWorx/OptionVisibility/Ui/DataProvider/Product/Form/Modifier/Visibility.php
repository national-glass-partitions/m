<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionVisibility\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\CustomOptions;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Container;
use Magento\Ui\Component\Form\Element\Checkbox;
use Magento\Ui\Component\Form\Element\DataType\Number;
use \Magento\Ui\Component\Form\Fieldset;
use Magento\Ui\Component\Form\Element\DataType\Text;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\Component\Form\Element\Hidden;
use Magento\Ui\Component\Form\Element\MultiSelect;
use Magento\Ui\Component\Modal;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use MageWorx\OptionBase\Ui\DataProvider\Product\Form\Modifier\ModifierInterface;
use MageWorx\OptionVisibility\Helper\Data as Helper;
use MageWorx\OptionBase\Helper\Data as HelperBase;
use Magento\Directory\Helper\Data;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Api\GroupManagementInterface;
use Magento\Customer\Api\Data\GroupInterface;

class Visibility extends AbstractModifier implements ModifierInterface
{
    const VISIBILITY_MODAL_INDEX = 'visibility_modal';
    const VISIBILITY_BUTTON_NAME = 'button_visibility';

    const MODAL_CONTENT     = 'content';
    const MODAL_FIELDSET    = 'fieldset';
    const MODAL_MULTISELECT = 'multiselect';
    const OPTION_VISIBILITY = 'option_visibility';

    const TEMPLATES_FORM_NAME = 'mageworx_optiontemplates_group_form';

    const KEY_CUSTOMER_GROUP = 'customer_group';
    const KEY_STORE_VIEW     = 'store_view';

    protected UrlInterface $urlBuilder;
    protected LocatorInterface $locator;
    protected ArrayManager $arrayManager;
    protected StoreManagerInterface $storeManager;
    protected Data $directoryHelper;
    protected SearchCriteriaBuilder $searchCriteriaBuilder;
    protected GroupRepositoryInterface $groupRepository;
    protected GroupManagementInterface $groupManagement;
    protected Helper $helper;
    protected HelperBase $helperBase;
    protected HttpRequest $request;
    protected array $meta = [];
    protected string $form = self::FORM_NAME;

    /**
     * @param ArrayManager $arrayManager
     * @param StoreManagerInterface $storeManager
     * @param LocatorInterface $locator
     * @param Data $directoryHelper
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param GroupRepositoryInterface $groupRepository
     * @param GroupManagementInterface $groupManagement
     * @param Helper $helper
     * @param HelperBase $helperBase
     * @param HttpRequest $request
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        LocatorInterface $locator,
        StoreManagerInterface $storeManager,
        Data $directoryHelper,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        GroupRepositoryInterface $groupRepository,
        GroupManagementInterface $groupManagement,
        ArrayManager $arrayManager,
        Helper $helper,
        HelperBase $helperBase,
        HttpRequest $request,
        UrlInterface $urlBuilder
    ) {
        $this->storeManager          = $storeManager;
        $this->locator               = $locator;
        $this->directoryHelper       = $directoryHelper;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->groupRepository       = $groupRepository;
        $this->groupManagement       = $groupManagement;
        $this->arrayManager          = $arrayManager;
        $this->helper                = $helper;
        $this->helperBase            = $helperBase;
        $this->request               = $request;
        $this->urlBuilder            = $urlBuilder;
    }

    /**
     * Get sort order of modifier to load modifiers in the right order
     *
     * @return int
     */
    public function getSortOrder()
    {
        return 40;
    }

    /**
     * @param array $data
     * @return array
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
            $this->form = static::TEMPLATES_FORM_NAME;
        }

        if ($this->helper->isVisibilityCustomerGroupEnabled() || $this->helper->isVisibilityStoreViewEnabled()) {
            $this->addOptionVisibilityModal();
            $this->addOptionVisibilityButton();
        }

        $groupCustomOptionsName    = CustomOptions::GROUP_CUSTOM_OPTIONS_NAME;
        $optionContainerName       = CustomOptions::CONTAINER_OPTION;
        $commonOptionContainerName = CustomOptions::CONTAINER_COMMON_NAME;

        // Add fields to the options
        $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
        [$optionContainerName]['children'][$commonOptionContainerName]['children'] = array_replace_recursive(
            $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
            [$optionContainerName]['children'][$commonOptionContainerName]['children'],
            $this->getOptionFeaturesFieldsConfig()
        );

        // Add fields to the values
        $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
        [$optionContainerName]['children']['values']['children']['record']['children'] = array_replace_recursive(
            $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
            [$optionContainerName]['children']['values']['children']['record']['children'],
            $this->getOptionValueFieldsConfig()
        );

        return $this->meta;
    }

    /**
     * The custom option fields config
     *
     * @return array
     */
    protected function getOptionFeaturesFieldsConfig()
    {
        $fields = [];

        if ($this->helper->isEnabledIsDisabled()) {
            $fields[Helper::KEY_DISABLED] = $this->getOptionDisabledHiddenConfig(72);
            $fields[Helper::KEY_DISABLED_BY_VALUES] = $this->getOptionDisabledByValuesHiddenConfig(73);
        }

        if ($this->helper->isVisibilityCustomerGroupEnabled()) {
            $field[static::KEY_CUSTOMER_GROUP] = $this->getCustomerGroupHidden(247);
        }

        if ($this->helper->isVisibilityStoreViewEnabled()) {
            $field[static::KEY_STORE_VIEW] = $this->getStoreViewHidden(248);
        }

        return $fields;
    }

    /**
     * The custom option value fields config
     *
     * @return array
     */
    protected function getOptionValueFieldsConfig()
    {
        $fields = [];

        if ($this->helper->isEnabledIsDisabled()) {
            $fields[Helper::KEY_DISABLED] = $this->getIsDisabledConfig(120);
        }

        return $fields;
    }

    /**
     * Is disabled value field config
     *
     * @param $sortOrder
     * @return array
     */
    protected function getIsDisabledConfig($sortOrder)
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label'         => __('Disabled'),
                        'componentType' => Field::NAME,
                        'formElement'   => Checkbox::NAME,
                        'dataScope'     => Helper::KEY_DISABLED,
                        'dataType'      => Number::NAME,
                        'prefer'        => 'toggle',
                        'valueMap'      => [
                            'true'  => Helper::DISABLED_TRUE,
                            'false' => Helper::DISABLED_FALSE,
                        ],
                        'fit'           => true,
                        'sortOrder'     => $sortOrder,
                    ],
                ],
            ],
        ];
    }

    /**
     * Is disabled option field config
     *
     * @param $sortOrder
     * @return array
     */
    protected function getOptionDisabledHiddenConfig($sortOrder)
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label'         => __('Disabled'),
                        'componentType' => Field::NAME,
                        'formElement'   => Hidden::NAME,
                        'dataScope'     => Helper::KEY_DISABLED,
                        'dataType'      => Number::NAME,
                        'prefer'        => 'toggle',
                        'valueMap'      => [
                            'true'  => Helper::DISABLED_TRUE,
                            'false' => Helper::DISABLED_FALSE,
                        ],
                        'fit'           => true,
                        'visible'       => false,
                        'sortOrder'     => $sortOrder,
                    ],
                ],
            ],
        ];
    }

    /**
     * Is disabled by values option field config
     *
     * @param $sortOrder
     * @return array
     */
    protected function getOptionDisabledByValuesHiddenConfig($sortOrder)
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label'         => __('Disabled by Values'),
                        'componentType' => Field::NAME,
                        'formElement'   => Hidden::NAME,
                        'dataScope'     => Helper::KEY_DISABLED_BY_VALUES,
                        'dataType'      => Number::NAME,
                        'prefer'        => 'toggle',
                        'valueMap'      => [
                            'true'  => Helper::DISABLED_TRUE,
                            'false' => Helper::DISABLED_FALSE,
                        ],
                        'fit'           => true,
                        'visible'       => false,
                        'sortOrder'     => $sortOrder,
                    ],
                ],
            ],
        ];
    }

    /**
     * Add modal window to configure visibility
     */
    protected function addOptionVisibilityModal()
    {
        $this->meta = array_merge_recursive(
            $this->meta,
            [
                static::VISIBILITY_MODAL_INDEX => $this->getModalConfig(),
            ]
        );
    }

    /**
     * Get visibility modal config
     */
    protected function getModalConfig()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'isTemplate'    => false,
                        'component'     => 'MageWorx_OptionVisibility/component/modal-component',
                        'componentType' => Modal::NAME,
                        'dataScope'     => static::OPTION_VISIBILITY,
                        'provider'      => static::FORM_NAME . '.' . static::FORM_NAME . '_data_source',
                        'options'       => [
                            'title'   => __('Manage Visibility'),
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
                                        'label'             => __('Visibility for'),
                                        'collapsible'       => false,
                                        'componentType'     => Fieldset::NAME,
                                        'component'         => 'MageWorx_OptionBase/component/fieldset',
                                        'dataScope'         => 'custom_data',
                                        'disabled'          => false
                                    ],
                                ],
                            ],
                            'children'  => [
                                Helper::KEY_DISABLED       => $this->getOptionDisabledConfig(),
                                static::KEY_CUSTOMER_GROUP => $this->getCustomerGroupStructure(),
                                static::KEY_STORE_VIEW     => $this->getStoreViewConfig()
                            ]
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Show button visibility
     */
    protected function addOptionVisibilityButton()
    {
        $groupCustomOptionsName    = CustomOptions::GROUP_CUSTOM_OPTIONS_NAME;
        $optionContainerName       = CustomOptions::CONTAINER_OPTION;
        $commonOptionContainerName = CustomOptions::CONTAINER_COMMON_NAME;

        $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
        [$optionContainerName]['children'][$commonOptionContainerName]['children'] = array_replace_recursive(
            $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
            [$optionContainerName]['children'][$commonOptionContainerName]['children'],
            $this->getVisibilityButtonConfig(122, true)
        );
    }

    /**
     * Get visibility button config
     *
     * @param int $sortOrder
     * @param bool $additionalForGroup
     * @return array
     */
    protected function getVisibilityButtonConfig($sortOrder, $additionalForGroup = false)
    {
        $params = [
            'provider'               => '${ $.provider }',
            'dataScope'              => '${ $.dataScope }',
            'formName'               => $this->form,
            'buttonName'             => '${ $.name }',
            'isCustomerGroupEnabled' => $this->helper->isVisibilityCustomerGroupEnabled(),
            'isStoreViewEnabled'     => $this->helper->isVisibilityStoreViewEnabled(),
            'isDisableEnabled'       => $this->helper->isEnabledIsDisabled()
        ];

        if ($this->helperBase->checkModuleVersion('104.0.0')) {
            $params['__disableTmpl'] = [
                'provider'   => false,
                'dataScope'  => false,
                'buttonName' => false
            ];
        }

        $field[static::VISIBILITY_BUTTON_NAME] = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'labelVisible'       => true,
                        'label'              => ' ',
                        'additionalClasses'  => 'mageworx-icon-additional-container',
                        'formElement'        => Container::NAME,
                        'componentType'      => Container::NAME,
                        'component'          => 'MageWorx_OptionBase/component/button',
                        'additionalForGroup' => $additionalForGroup,
                        'displayArea'        => 'insideGroup',
                        'template'           => 'ui/form/components/button/container',
                        'elementTmpl'        => 'MageWorx_OptionBase/button',
                        'buttonClasses'      => 'mageworx-icon visibility',
                        'mageworxAttributes' => $this->getEnabledAttributes(),
                        'displayAsLink'      => false,
                        'sortOrder'          => $sortOrder,
                        'tooltipTpl'         => 'MageWorx_OptionBase/tooltip',
                        'tooltip'            => [
                            'description' => __('Visibility')
                        ],
                        'actions'            => [
                            [
                                'targetName' => 'ns=' . $this->form . ', index='
                                    . static::VISIBILITY_MODAL_INDEX,
                                'actionName' => 'openModal',
                            ],
                            [
                                'targetName' => 'ns=' . $this->form . ', index='
                                    . static::VISIBILITY_MODAL_INDEX,
                                'actionName' => 'reloadModal',
                                'params'     => [
                                    $params,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return $field;
    }

    /**
     * Get enabled attributes
     *
     * @return array
     */
    public function getEnabledAttributes()
    {
        $attributes = [];
        if ($this->helper->isEnabledIsDisabled()) {
            $attributes[Helper::KEY_DISABLED] = '${ $.dataScope }' . '.' . Helper::KEY_DISABLED;
        }
        if ($this->helper->isVisibilityCustomerGroupEnabled()) {
            $attributes[static::KEY_CUSTOMER_GROUP] = '${ $.dataScope }' . '.' . static::KEY_CUSTOMER_GROUP;
        }
        if ($this->helper->isVisibilityStoreViewEnabled()) {
            $attributes[static::KEY_STORE_VIEW] = '${ $.dataScope }' . '.' . static::KEY_STORE_VIEW;
        }

        if ($this->helperBase->checkModuleVersion('104.0.0')) {
            $attributes['__disableTmpl'] = [
                Helper::KEY_DISABLED       => false,
                static::KEY_CUSTOMER_GROUP => false,
                static::KEY_STORE_VIEW     => false,
            ];
        }

        return $attributes;
    }

    /**
     * Get customer group hidden config
     *
     * @param $sortOrder
     * @return array
     */
    protected function getCustomerGroupHidden($sortOrder)
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType'      => Field::NAME,
                        'formElement'        => Hidden::NAME,
                        'dataScope'          => static::KEY_CUSTOMER_GROUP,
                        'dataType'           => Text::NAME,
                        'additionalForGroup' => true,
                        'displayArea'        => 'insideGroup',
                        'sortOrder'          => $sortOrder,
                    ],
                ],
            ],
        ];
    }

    /**
     * Get store view hidden config
     *
     * @param $sortOrder
     * @return array
     */
    protected function getStoreViewHidden($sortOrder)
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType'      => Field::NAME,
                        'formElement'        => Hidden::NAME,
                        'dataScope'          => static::KEY_STORE_VIEW,
                        'dataType'           => Text::NAME,
                        'additionalForGroup' => true,
                        'displayArea'        => 'insideGroup',
                        'sortOrder'          => $sortOrder,
                    ],
                ],
            ],
        ];
    }

    /**
     * Get Customer Group structure
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getCustomerGroupStructure()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label'         => __('Customer Group'),
                        'dataScope'     => 'customer_group_id',
                        'formElement'   => MultiSelect::NAME,
                        'componentType' => Field::NAME,
                        'dataType'      => Text::NAME,
                        'options'       => $this->getCustomerGroups(),
                        'value'         => $this->getDefaultCustomerGroup(),
                        'tooltip'       => [
                            'description' => __('Choose the customer groups the option should be available for.')
                        ],
                        'validation'    => [
                            'required-entry' => false,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get store view config
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getStoreViewConfig()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label'         => __('Stores'),
                        'dataScope'     => 'store_view_id',
                        'formElement'   => MultiSelect::NAME,
                        'componentType' => Field::NAME,
                        'dataType'      => Text::NAME,
                        'options'       => $this->getStores(),
                        'value'         => $this->getDefaultStore(),
                        'tooltip'       => [
                            'description' => __('Choose the store views the option should be displayed on.')
                        ],
                        'validation'    => [
                            'required-entry' => false,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Is disabled field config for option modal
     *
     * @return array
     */
    protected function getOptionDisabledConfig()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label'         => __('Disabled'),
                        'componentType' => Field::NAME,
                        'formElement'   => Checkbox::NAME,
                        'dataScope'     => Helper::KEY_DISABLED,
                        'dataType'      => Number::NAME,
                        'prefer'        => 'toggle',
                        'valueMap'      => [
                            'true'  => Helper::DISABLED_TRUE,
                            'false' => Helper::DISABLED_FALSE,
                        ],
                        'fit'           => true,
                        'tooltip'       => [
                            'description' => __(
                                'This setting disables the particular option with all its values
                                despite of the selected customer groups and the store views. If you need to
                                disable a particular option value, you should enable this setting for that value.'
                            )
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getCustomerGroups()
    {
        $customerGroups = [
            [
                'label' => __('ALL GROUPS'),
                'value' => GroupInterface::CUST_GROUP_ALL,
            ]
        ];

        /** @var GroupInterface[] $groups */
        $groups = $this->groupRepository->getList($this->searchCriteriaBuilder->create());
        foreach ($groups->getItems() as $group) {
            $customerGroups[] = [
                'label' => $group->getCode(),
                'value' => $group->getId(),
            ];
        }

        return $customerGroups;
    }

    /**
     * @return array
     */
    protected function getStores()
    {
        $stores = [
            [
                'label' => __('All Stores'),
                'value' => 0,
            ]
        ];

        $storesList = $this->storeManager->getStores();
        if (empty($storesList)) {
            return $stores;
        }

        foreach ($storesList as $store) {
            /** @var \Magento\Store\Model\Store $store */
            $stores[] = [
                'label' => $store->getName(),
                'value' => $store->getId(),
            ];
        }

        return $stores;
    }

    /**
     * Retrieve default value for store
     *
     * @return int
     */
    protected function getDefaultStore()
    {
        $defaultStoreView = $this->storeManager->getDefaultStoreView();
        return is_null($defaultStoreView) ? 0 : $defaultStoreView->getId();
    }

    /**
     * Show website column for group price's grid
     *
     * @return bool
     */
    protected function isShowWebsiteColumn()
    {
        if ($this->storeManager->isSingleStoreMode()) {
            return false;
        }

        return true;
    }

    /**
     * Retrieve default value for customer group
     *
     * @return int|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getDefaultCustomerGroup()
    {
        return $this->groupManagement->getAllCustomersGroup()->getId();
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
