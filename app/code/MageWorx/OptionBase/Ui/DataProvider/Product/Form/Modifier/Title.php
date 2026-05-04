<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionBase\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\CustomOptions;
use Magento\Framework\Serialize\Serializer\Json as Serializer;
use Magento\Ui\Component\Form\Element\DataType\Number;
use Magento\Ui\Component\Form\Element\Input;
use Magento\Ui\Component\Form\Element\DataType\Text;
use Magento\Framework\App\Request\Http;
use MageWorx\OptionBase\Helper\Data as BaseHelper;
use MageWorx\OptionBase\Model\OptionTitle;
use Magento\Ui\Component\Modal;
use Magento\Ui\Component\Container;
use Magento\Ui\Component\Form\Fieldset;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\Component\Form\Element\Checkbox;
use Magento\Store\Model\StoreManagerInterface;

class Title extends AbstractModifier implements ModifierInterface
{
    const TITLE_MODAL_INDEX = 'title_modal';
    const TITLE_BUTTON_NAME = 'title_button';
    const TITLE             = 'title';

    const PATH_GROUP_CONTAINER = 'group_container_';
    const PATH_TITLE           = 'title_';
    const PATH_USE_GLOBAL      = 'use_global_';

    const MODAL_CONTENT  = 'content';
    const MODAL_FIELDSET = 'fieldset';

    protected Http $request;
    protected array $meta = [];
    protected string $form = 'product_form';
    protected StoreManagerInterface $storeManager;
    protected BaseHelper $baseHelper;
    protected array $storeIds = [];
    protected Serializer $serializer;

    /**
     * Title constructor.
     *
     * @param StoreManagerInterface $storeManager
     * @param Http $request
     * @param BaseHelper $baseHelper
     * @param Serializer $serializer
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Http $request,
        BaseHelper $baseHelper,
        Serializer $serializer
    ) {
        $this->storeManager = $storeManager;
        $this->request      = $request;
        $this->baseHelper   = $baseHelper;
        $this->serializer   = $serializer;
    }

    /**
     * Get sort order of modifier to load modifiers in the right order
     *
     * @return int
     */
    public function getSortOrder()
    {
        return 51;
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

        if ($this->isTemplatePage()) {
            $this->form = 'mageworx_optiontemplates_group_form';
        }

        if (!$this->storeManager->isSingleStoreMode()) {
            $this->addModal();
            $this->addButtons();
        }

        return $this->meta;
    }

    /**
     * Add modal windows to manage titles
     */
    protected function addModal()
    {
        $this->meta = array_merge_recursive(
            $this->meta,
            [
                static::TITLE_MODAL_INDEX => $this->getTitleModalConfig()
            ]
        );
    }

    /**
     * Get option title modal config
     */
    protected function getTitleModalConfig()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'isTemplate'    => false,
                        'component'     => 'MageWorx_OptionBase/js/component/modal-title',
                        'componentType' => Modal::NAME,
                        'dataScope'     => OptionTitle::KEY_MAGEWORX_OPTION_TITLE,
                        'provider'      => static::FORM_NAME . '.' . static::FORM_NAME . '_data_source',
                        'options'       => [
                            'title'   => __('Store View Titles'),
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
                            ],
                        ],
                    ],
                    'children'  => [
                        static::MODAL_FIELDSET => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'additionalClasses' => 'admin__fieldset-product-websites',
                                        'label'             => __('Titles for'),
                                        'collapsible'       => false,
                                        'componentType'     => Fieldset::NAME,
                                        'component'         => 'MageWorx_OptionBase/component/fieldset',
                                        'dataScope'         => 'custom_data',
                                        'disabled'          => false
                                    ],
                                ],
                            ],
                            'children'  => $this->getTitleGroups()
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
    protected function getTitleGroups()
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
                'children'  => $this->getTitleFields($storeItem)
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
    protected function getTitleFields($storeItem)
    {
        $fields = [];

        if ($storeItem['is_enabled_use_global']) {
            $fields[self::PATH_USE_GLOBAL . $storeItem['store_id']] = [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'label'         => __('Use Global Title'),
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

        $fields[self::PATH_TITLE . $storeItem['store_id']] = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'labelVisible' => false,
                        'componentType' => Field::NAME,
                        'formElement'   => Input::NAME,
                        'dataType'      => Text::NAME,
                        'sortOrder'     => 20,
                        'validation'    => [
                            'required-entry' => false
                        ],
                        'listens'       => [
                            'disabled' => 'setDisabled',
                            'value'    => 'value'
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
     * Show title buttons
     */
    protected function addButtons()
    {
        $groupCustomOptionsName    = CustomOptions::GROUP_CUSTOM_OPTIONS_NAME;
        $optionContainerName       = CustomOptions::CONTAINER_OPTION;
        $commonOptionContainerName = CustomOptions::CONTAINER_COMMON_NAME;

        $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
        [$optionContainerName]['children'][$commonOptionContainerName]['children'] = array_replace_recursive(
            $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
            [$optionContainerName]['children'][$commonOptionContainerName]['children'],
            $this->getTitleButtonConfig(126, true)
        );

        $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
        [$optionContainerName]['children']['values']['children']['record']['children'] = array_replace_recursive(
            $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
            ['container_option']['children']['values']['children']['record']['children'],
            $this->getTitleButtonConfig(209)
        );
    }

    /**
     * Get title button config
     *
     * @param int $sortOrder
     * @param bool $additionalForGroup
     * @return array
     */
    protected function getTitleButtonConfig($sortOrder, $additionalForGroup = false)
    {
        $storeIds     = $this->storeIds;
        $storeIdsJson = $storeIds ? $this->serializer->serialize($storeIds) : null;

        $params = [
            'provider'           => '${ $.provider }',
            'dataScope'          => '${ $.dataScope }',
            'formName'           => $this->form,
            'buttonName'         => '${ $.name }',
            'storeIds'           => $storeIdsJson,
            'pathGroupContainer' => self::PATH_GROUP_CONTAINER,
            'pathTitle'          => self::PATH_TITLE,
            'pathUseGlobal'      => self::PATH_USE_GLOBAL,
            'fieldName'          => OptionTitle::KEY_MAGEWORX_OPTION_TITLE,
            'currentStoreId'     => $this->isTemplatePage()
                ? 0
                : $this->storeManager->getStore()->getStoreId()
        ];

        if ($this->baseHelper->checkModuleVersion('104.0.0')) {
            $params['__disableTmpl'] =
            [
                'provider'   => false,
                'dataScope'  => false,
                'buttonName' => false
            ];
        }

        $field[static::TITLE_BUTTON_NAME] = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'labelVisible'       => true,
                        'label'              => ' ',
                        'formElement'        => Container::NAME,
                        'componentType'      => Container::NAME,
                        'component'          => 'MageWorx_OptionBase/component/button',
                        'additionalForGroup' => $additionalForGroup,
                        'additionalClasses'  => $additionalForGroup
                            ? 'mageworx-icon-additional-container'
                            : '',
                        'displayArea'        => 'insideGroup',
                        'template'           => 'ui/form/components/button/container',
                        'elementTmpl'        => 'MageWorx_OptionBase/button',
                        'buttonClasses'      => 'mageworx-icon title',
                        'tooltipTpl'         => 'MageWorx_OptionBase/tooltip',
                        'tooltip'            => [
                            'description' => __('Store View Titles')
                        ],
                        'mageworxAttributes' => $this->getEnabledAttributes(),
                        'displayAsLink'      => false,
                        'fit'                => true,
                        'sortOrder'          => $sortOrder,
                        'actions'            => [
                            [
                                'targetName' => 'ns=' . $this->form . ', index='
                                    . static::TITLE_MODAL_INDEX,
                                'actionName' => 'openModal',
                            ],
                            [
                                'targetName' => 'ns=' . $this->form . ', index='
                                    . static::TITLE_MODAL_INDEX,
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

        $attributes['title'] = '${ $.dataScope }' . '.' . 'title';

        if ($this->baseHelper->checkModuleVersion('104.0.0')) {
            $attributes['__disableTmpl'] = [
                'title' => false
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

    /**
     * Check if it is template page
     *
     * @return bool
     */
    protected function isTemplatePage()
    {
        return $this->request->getRouteName() === 'mageworx_optiontemplates';
    }
}
