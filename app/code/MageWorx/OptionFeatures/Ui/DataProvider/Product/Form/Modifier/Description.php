<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionFeatures\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\CustomOptions;
use Magento\Framework\Serialize\Serializer\Json as Serializer;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Container;
use Magento\Ui\Component\Form\Element\Checkbox;
use Magento\Ui\Component\Form\Element\DataType\Number;
use Magento\Ui\Component\Form\Element\Wysiwyg;
use Magento\Ui\Component\Form\Fieldset;
use Magento\Ui\Component\Form\Field;
use MageWorx\OptionBase\Helper\Data as HelperBase;
use MageWorx\OptionBase\Ui\DataProvider\Product\Form\Modifier\ModifierInterface;
use MageWorx\OptionFeatures\Helper\Data as Helper;
use Magento\Ui\Component\Modal;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\Request\Http;

class Description extends AbstractModifier implements ModifierInterface
{
    const MODAL_CONTENT  = 'content';
    const MODAL_FIELDSET = 'fieldset';

    const DESCRIPTION_MODAL_INDEX = 'description_modal';
    const DESCRIPTION_BUTTON_NAME = 'button_description';
    const DESCRIPTION             = 'description';

    const PATH_GROUP_CONTAINER = 'group_container_';
    const PATH_DESCRIPTION     = 'description_';
    const PATH_USE_GLOBAL      = 'use_global_';

    const GLOBAL_DESCRIPTION_TEXTAREA = 'global_description_textarea';

    protected UrlInterface $urlBuilder;
    protected ArrayManager $arrayManager;
    protected StoreManagerInterface $storeManager;
    protected LocatorInterface $locator;
    protected Helper $helper;
    protected HelperBase $helperBase;
    protected Http $request;
    protected array $meta = [];
    protected string $form = 'product_form';
    protected array $storeIds = [];
    protected Serializer $serializer;

    /**
     * Description constructor.
     *
     * @param ArrayManager $arrayManager
     * @param StoreManagerInterface $storeManager
     * @param LocatorInterface $locator
     * @param Helper $helper
     * @param Http $request
     * @param UrlInterface $urlBuilder
     * @param HelperBase $helperBase
     * @param Serializer $serializer
     */
    public function __construct(
        ArrayManager $arrayManager,
        StoreManagerInterface $storeManager,
        LocatorInterface $locator,
        Helper $helper,
        Http $request,
        UrlInterface $urlBuilder,
        HelperBase $helperBase,
        Serializer $serializer
    ) {
        $this->arrayManager = $arrayManager;
        $this->storeManager = $storeManager;
        $this->locator      = $locator;
        $this->helper       = $helper;
        $this->request      = $request;
        $this->urlBuilder   = $urlBuilder;
        $this->helperBase   = $helperBase;
        $this->serializer   = $serializer;
    }

    /**
     * Get sort order of modifier to load modifiers in the right order
     *
     * @return int
     */
    public function getSortOrder()
    {
        return 80;
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

        if ($this->helper->isOptionDescriptionEnabled() || $this->helper->isOptionValueDescriptionEnabled()) {
            $this->addDescriptionModal();
        }
        $this->addDescriptionButtons();

        return $this->meta;
    }

    /**
     * Add modal window to manage option/value descriptions
     */
    protected function addDescriptionModal()
    {
        $this->meta = array_merge_recursive(
            $this->meta,
            [
                static::DESCRIPTION_MODAL_INDEX => $this->getDescriptionModalConfig(),
            ]
        );
    }

    /**
     * Get description modal config
     */
    protected function getDescriptionModalConfig()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'isTemplate'    => false,
                        'component'     => 'MageWorx_OptionFeatures/js/component/modal-description',
                        'componentType' => Modal::NAME,
                        'dataScope'     => static::DESCRIPTION,
                        'provider'      => static::FORM_NAME . '.' . static::FORM_NAME . '_data_source',
                        'options'       => [
                            'title'   => __('Manage Descriptions'),
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
                                        'label'             => __('Descriptions for'),
                                        'collapsible'       => false,
                                        'componentType'     => Fieldset::NAME,
                                        'component'         => 'MageWorx_OptionBase/component/fieldset',
                                        'dataScope'         => 'custom_data',
                                        'disabled'          => false
                                    ],
                                ],
                            ],
                            'children'  => $this->getDescriptionGroups()
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
    protected function getDescriptionGroups()
    {
        $groups    = [];
        $stores    = $this->getStores();
        $sortOrder = 10;
        foreach ($stores as $storeItem) {
            $groups[self::PATH_GROUP_CONTAINER . $storeItem['store_id']] = [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'label'             => $storeItem['label'],
                            'componentType'     => Container::NAME,
                            'formElement'       => Container::NAME,
                            'component'         => 'Magento_Ui/js/form/components/group',
                            'breakLine'         => true,
                            'showLabel'         => true,
                            'additionalClasses' =>
                                'admin__field-control admin__control-grouped admin__field-group-columns',
                            'sortOrder'         => $sortOrder,
                        ],
                    ],
                ],
                'children'  => $this->getDescriptionFields($storeItem)
            ];

            $sortOrder += 10;
        }

        return $groups;
    }

    /**
     * Get store view config
     *
     * @param array $storeItem
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getDescriptionFields($storeItem)
    {
        $fields = [];

        if ($storeItem['is_enabled_use_global']) {
            $fields[self::PATH_USE_GLOBAL . $storeItem['store_id']] = [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'label'         => __('Use Global Description'),
                            'componentType' => Field::NAME,
                            'formElement'   => Checkbox::NAME,
                            'dataType'      => Number::NAME,
                            'prefer'        => 'toggle',
                            'store_id'      => $storeItem['store_id'],
                            'value'         => 1,
                            'valueMap'      => [
                                'true'  => 1,
                                'false' => 0,
                            ],
                            'fit'           => true,
                            'sortOrder'     => 10,
                        ],
                    ],
                ],
            ];
        }

        $fields[self::PATH_DESCRIPTION . $storeItem['store_id']] = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'component'         => 'Magento_Ui/js/form/element/wysiwyg',
                        'componentType'     => Field::NAME,
                        'formElement'       => Wysiwyg::NAME,
                        'dataType'          => Wysiwyg::NAME,
                        'sortOrder'         => 20,
                        'store_id'          => $storeItem['store_id'],
                        'additionalClasses' => 'admin__control-wysiwig',
                        'validation'        => [
                            'required-entry' => false
                        ],
                        'listens'           => [
                            'disabled' => 'setDisabled',
                            'value'    => 'value'
                        ],
                        'wysiwyg'           => (bool)$this->helper->isEnabledWysiwygForDescription(),
                        'wysiwygConfigData' => [
                            'add_variables'  => false,
                            'add_widgets'    => false,
                            'add_images'     => false,
                            'use_container'  => true,
                            'is_pagebuilder_enabled' => false,
                            'enabled'        => (bool)$this->helper->isEnabledWysiwygForDescription()
                        ],
                    ],
                ],
            ],
        ];

        return $fields;
    }

    /**
     * Get stores
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getStores()
    {
        $stores   = [];
        $storeIds = [0];
        $stores[] = [
            'label'                 => 'Global',
            'store_id'              => 0,
            'is_enabled_use_global' => 0
        ];

        foreach ($this->storeManager->getStores() as $storeItem) {
            $stores[]   = [
                'label'                 => $storeItem->getName(),
                'store_id'              => $storeItem->getStoreId(),
                'is_enabled_use_global' => 1
            ];
            $storeIds[] = (int)$storeItem->getStoreId();
        }
        $this->storeIds = $storeIds;

        return $stores;
    }

    /**
     * Show description buttons
     */
    protected function addDescriptionButtons()
    {
        $groupCustomOptionsName    = CustomOptions::GROUP_CUSTOM_OPTIONS_NAME;
        $optionContainerName       = CustomOptions::CONTAINER_OPTION;
        $commonOptionContainerName = CustomOptions::CONTAINER_COMMON_NAME;

        if ($this->helper->isOptionDescriptionEnabled()) {
            $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
            [$optionContainerName]['children'][$commonOptionContainerName]['children'] = array_replace_recursive(
                $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
                [$optionContainerName]['children'][$commonOptionContainerName]['children'],
                $this->getDescriptionButtonConfig(128, true)
            );
        }

        if ($this->helper->isOptionValueDescriptionEnabled()) {
            $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
            [$optionContainerName]['children']['values']['children']['record']['children'] = array_replace_recursive(
                $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
                ['container_option']['children']['values']['children']['record']['children'],
                $this->getDescriptionButtonConfig(211)
            );
        }
    }

    /**
     * Get option description button config
     *
     * @param int $sortOrder
     * @param bool $additionalForGroup
     * @return array
     */
    protected function getDescriptionButtonConfig($sortOrder, $additionalForGroup = false)
    {
        $params = [
            'provider'           => '${ $.provider }',
            'dataScope'          => '${ $.dataScope }',
            'formName'           => $this->form,
            'buttonName'         => '${ $.name }',
            'isWysiwygEnabled'   => (bool)$this->helper->isEnabledWysiwygForDescription(),
            'storeIds'           => $this->serializer->serialize($this->storeIds),
            'pathGroupContainer' => self::PATH_GROUP_CONTAINER,
            'pathDescription'    => self::PATH_DESCRIPTION,
            'pathUseGlobal'      => self::PATH_USE_GLOBAL
        ];

        if ($this->helperBase->checkModuleVersion('104.0.0')) {
            $params['__disableTmpl'] = [
                'provider'   => false,
                'dataScope'  => false,
                'buttonName' => false
            ];
        }

        $mageworxAttributes = [
            static::DESCRIPTION => '${ $.dataScope }' . '.' . static::DESCRIPTION
        ];

        if ($this->helperBase->checkModuleVersion('104.0.0')) {
            $mageworxAttributes['__disableTmpl'] = [
                static::DESCRIPTION => false
            ];
        }

        $field[static::DESCRIPTION_BUTTON_NAME] = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'labelVisible'       => true,
                        'label'              => ' ',
                        'formElement'        => Container::NAME,
                        'componentType'      => Container::NAME,
                        'component'          => 'MageWorx_OptionBase/component/button',
                        'additionalForGroup' => $additionalForGroup,
                        'displayArea'        => 'insideGroup',
                        'template'           => 'ui/form/components/button/container',
                        'elementTmpl'        => 'MageWorx_OptionBase/button',
                        'buttonClasses'      => 'mageworx-icon description',
                        'tooltipTpl'         => 'MageWorx_OptionBase/tooltip',
                        'tooltip'            => [
                            'description' => __('Description')
                        ],
                        'mageworxAttributes' => $mageworxAttributes,
                        'displayAsLink'      => false,
                        'fit'                => true,
                        'sortOrder'          => $sortOrder,
                        'actions'            => [
                            [
                                'targetName' => 'ns=' . $this->form . ', index='
                                    . static::DESCRIPTION_MODAL_INDEX,
                                'actionName' => 'openModal',
                            ],
                            [
                                'targetName' => 'ns=' . $this->form . ', index='
                                    . static::DESCRIPTION_MODAL_INDEX,
                                'actionName' => 'reloadModal',
                                'params'     => [
                                    $params
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        if ($additionalForGroup) {
            $field[static::DESCRIPTION_BUTTON_NAME]['arguments']['data']['config']['additionalClasses'] =
                'mageworx-icon-additional-container';
        }

        return $field;
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
