<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionDependency\Ui\DataProvider\Product\Form\Modifier;

use \MageWorx\OptionBase\Ui\DataProvider\Product\Form\Modifier\ModifierInterface;
use \Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use \Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\CustomOptions;
use \Magento\Framework\Stdlib\ArrayManager;
use \Magento\Catalog\Model\Locator\LocatorInterface;
use \Magento\Ui\Component\Container;
use \Magento\Ui\Component\Form\Fieldset;
use \Magento\Ui\Component\Modal;
use \Magento\Ui\Component\Form\Element\DataType\Text;
use \Magento\Ui\Component\Form\Element\Hidden;
use \Magento\Ui\Component\Form\Element\Select;
use \Magento\Ui\Component\Form\Element\Input;
use \Magento\Ui\Component\Form\Field;
use \MageWorx\OptionDependency\Helper\Data as Helper;
use \MageWorx\OptionBase\Helper\Data as HelperBase;
use \Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\UrlInterface;

/**
 * Class DisableFields. Update custom options grid in product edit page.
 * Add 'sku_is_valid' hidden field.
 * Update 'disabled' attribute on some option values fields.
 */
class Dependency extends AbstractModifier implements ModifierInterface
{
    const CUSTOM_MODAL_LINK = 'custom_modal_link';

    const DEPENDENCY_TREE = 'dependency_tree';
    const DEPENDENCY_TYPE = 'dependency_type';

    const DEPENDENCY_LAYOUT         = 'dependency_index';
    const DEPENDENCY_FORM           = 'dependency_form';
    const DEPENDENCY_MODAL_INDEX    = 'dependency_modal';
    const DEPENDENCY_MODAL_CONTENT  = 'content';
    const DEPENDENCY_MODAL_FIELDSET = 'fieldset';

    const DEPENDENCY_BUTTON_NAME = 'button_dependency';
    const FIELD_DEPENDENCY = 'dependency';
    const FIELD_OPTION_TYPE_TITLE_ID = 'option_type_title_id';
    const FIELD_OPTION_TITLE_ID = 'option_title_id';

    const TEMPLATES_FORM_NAME = 'mageworx_optiontemplates_group_form';

    protected UrlInterface $urlBuilder;
    protected LocatorInterface $locator;
    protected ArrayManager $arrayManager;
    protected Helper $helper;
    protected HelperBase $helperBase;
    protected HttpRequest $request;
    protected array $meta = [];
    protected string $form = self::FORM_NAME;

    public function __construct(
        LocatorInterface $locator,
        ArrayManager $arrayManager,
        Helper $helper,
        HelperBase $helperBase,
        HttpRequest $request,
        UrlInterface $urlBuilder
    ) {
        $this->locator = $locator;
        $this->arrayManager = $arrayManager;
        $this->helper = $helper;
        $this->helperBase = $helperBase;
        $this->request = $request;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Get sort order of modifier to load modifiers in the right order
     *
     * @return int
     */
    public function getSortOrder()
    {
        return 90;
    }

    public function modifyData(array $data)
    {
        if (!$this->isSchedule()) {
            return $data;
        }

        $productId = $this->locator->getProduct()->getId();
        $productOptions = isset($data[$productId]['product']['options']) ? $data[$productId]['product']['options'] : [];

        // convert mageworx_option_id to record_id in the dependencies
        $productOptions = $this->helperBase->convertDependentIdToRecordId($productOptions);
        $productOptions = $this->helperBase->clearId($productOptions);
        $data[$productId]['product']['options'] = $productOptions;

        return $data;
    }

    private function isSchedule()
    {
        if ($this->request->getParam('handle') != 'catalogstaging_update') {
            return false;
        }

        return true;
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

        if ($this->helper->isTitleIdEnabled()) {
            $this->addOptionTitleId();
            $this->addOptionTypeTitleId();
        }

        $this->addDependencyModal();
        $this->addDependencyButton();
        $this->addDependencyType();

        return $this->meta;
    }

    /**
     * Add modal window for configure dependencies.
     */
    protected function addDependencyModal()
    {
        $this->meta = array_merge_recursive(
            $this->meta,
            [
                static::DEPENDENCY_MODAL_INDEX => $this->getModalConfig(),
            ]
        );
    }

    protected function getModalConfig()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'isTemplate' => false,
                        'component' => 'MageWorx_OptionDependency/component/modal-component',
                        'componentType' => Modal::NAME,
                        'dataScope' => static::DEPENDENCY_TREE,
                        'provider' => static::FORM_NAME . '.' . static::FORM_NAME . '_data_source',
                        'indexies' => [
                            static::DEPENDENCY_TREE => static::DEPENDENCY_TREE,
                            static::DEPENDENCY_TYPE => static::DEPENDENCY_TYPE,
                        ],
                        'options' => [
                            'title' => __('Manage Dependencies'),
                            'buttons' => [
                                [
                                    'text' => __('Save & Close'),
                                    'class' => 'action-primary',
                                    'actions' => [
                                        'save',
                                    ],
                                ],
                            ],
                        ],
                        'imports' => [
                            'state' => '!index=' . static::DEPENDENCY_MODAL_CONTENT . ':responseStatus',
                        ],
                    ],
                ],
            ],
            'children' => [
                static::DEPENDENCY_MODAL_CONTENT => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'autoRender' => false,
                                'componentType' => 'container',
                                'dataScope' => 'data.product',
                                'externalProvider' => 'data.product_data_source',
                                'ns' => static::FORM_NAME,
                                'behaviourType' => 'edit',
                                'externalFilterMode' => true,
                                'currentProductId' => $this->locator->getProduct()->getId(),
                            ],
                        ],
                    ],
                    'children' => [
                        static::DEPENDENCY_MODAL_FIELDSET => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'additionalClasses' => 'admin__fieldset-product-websites',
                                        'label' => __('Dependency'),
                                        'collapsible' => false,
                                        'componentType' => Fieldset::NAME,
                                        'component' => 'MageWorx_OptionDependency/component/fieldset',
                                        'dataScope' => 'custom_data',
                                        'disabled' => false
                                    ],
                                ],
                            ],
                            'children' => [
                                static::DEPENDENCY_TYPE => $this->getDependencyTypeDropdown(),
                                static::DEPENDENCY_TREE => $this->getDependencyTree(),
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Retrieve config of Dependency Type.
     *
     * @param int $sortOrder
     * @return array
     */
    protected function getDependencyTypeDropdown()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => __('Dependency Type'),
                        'dataType' => Text::NAME,
                        'formElement' => Select::NAME,
                        'componentType' => Field::NAME,
                        'dataScope' => 'dependency_type',
                        'visible' => true,
                        'options' => [['value' => 0, 'label' => 'OR'],['value' => 1, 'label' => 'AND']],
                        'validation' => [
                            'required-entry' => false,
                        ],
                        'tooltip' => [
                            'description' => __('The "Dependency Type" setting defines the conditions to display the current option value on the front-end.') .
                                ' ' .
                                __('AND - all parent options values should be selected to display this option value.') .
                                ' ' .
                                __('OR - any of parent option values might be selected so the current option value will be displayed.').
                                ' ' .
                                __('The "Parent Options" field allows choosing the option values that should be selected to display the current option value on the front-end.')
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Retrieve config of dependency tree component.
     *
     * @return array
     */
    protected function getDependencyTree()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => __('Parent Options'),
                        'formElement' => 'select',
                        'componentType' => 'field',
                        'component' => 'MageWorx_OptionDependency/component/dependency-tree',
                        'filterOptions' => true,
                        'chipsEnabled' => true,
                        'disableLabel' => true,
                        'levelsVisibility' => '1',
                        'elementTmpl' => 'MageWorx_OptionDependency/dependency-tree',
                        'optgroupTmpl' => 'MageWorx_OptionDependency/dependency-tree-optgroup',
                        'options' => []
                    ],
                ],
            ],
        ];
    }

    protected function addDependencyButton()
    {
        $groupCustomOptionsName = CustomOptions::GROUP_CUSTOM_OPTIONS_NAME;
        $optionContainerName = CustomOptions::CONTAINER_OPTION;
        $containerTypeStatic = CustomOptions::CONTAINER_TYPE_STATIC_NAME;

        // add 'Dependency' button to option values
        $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
        [$optionContainerName]['children']['values']['children']['record']['children'] = array_replace_recursive(
            $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
            ['container_option']['children']['values']['children']['record']['children'],
            $this->getDependencyButtonConfig(203)
        );

        // add 'Dependency' button to options
        $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
        ['container_option']['children'][$containerTypeStatic]['children'] = array_replace_recursive(
            $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
            ['container_option']['children'][$containerTypeStatic]['children'],
            $this->getDependencyButtonConfig(120, true)
        );
    }

    protected function getDependencyButtonConfig($sortOrder, $additionalForGroup = false)
    {
        $params = [
            'provider' => '${ $.provider }',
            'dataScope' => '${ $.dataScope }',
            'buttonName' => '${ $.name }',
            'isEnabledTitleId' => $this->helper->isTitleIdEnabled(),
            'isProductPage' => $this->form == static::FORM_NAME ? true : false
        ];

        if ($this->helperBase->checkModuleVersion('104.0.0')) {
            $params['__disableTmpl'] = [
                'provider'   => false,
                'dataScope'  => false,
                'buttonName' => false
            ];
        }

        $mageworxAttributes = [
            static::FIELD_DEPENDENCY => '${ $.dataScope }' . '.' . static::FIELD_DEPENDENCY
        ];

        if ($this->helperBase->checkModuleVersion('104.0.0')) {
            $mageworxAttributes['__disableTmpl'] = [
                static::FIELD_DEPENDENCY => false
            ];
        }

        $field[static::DEPENDENCY_BUTTON_NAME] = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'labelVisible' => true,
                        'label' => ' ',
                        'buttonClasses' => 'mageworx-icon dependency',
                        'mageworxAttributes' => $mageworxAttributes,
                        'formElement' => Container::NAME,
                        'componentType' => Container::NAME,
                        'component' => 'MageWorx_OptionBase/component/button',
                        'additionalForGroup' => $additionalForGroup,
                        'displayArea' => 'insideGroup',
                        'template' => 'ui/form/components/button/container',
                        'elementTmpl' => 'MageWorx_OptionBase/button',
                        'displayAsLink' => false,
                        'sortOrder' => $sortOrder,
                        'tooltipTpl'         => 'MageWorx_OptionBase/tooltip',
                        'tooltip'            => [
                            'description' => __('Dependency')
                        ],
                        'actions' => [
                            [
                                'targetName' => 'ns=' . $this->form . ', index='
                                    . static::DEPENDENCY_MODAL_INDEX,
                                'actionName' => 'openModal',
                            ],
                            [
                                'targetName' => 'ns=' . $this->form . ', index='
                                    . static::DEPENDENCY_MODAL_INDEX,
                                'actionName' => 'reloadModal',
                                'params' => [
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
     * Add 'Dependency' field to options.
     */
    protected function addDependencyField()
    {
        $groupCustomOptionsName = CustomOptions::GROUP_CUSTOM_OPTIONS_NAME;
        $optionContainerName = CustomOptions::CONTAINER_OPTION;

        // add 'Dependency' button to option values
        $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
        [$optionContainerName]['children']['values']['children']['record']['children'] = array_replace_recursive(
            $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
            [$optionContainerName]['children']['values']['children']['record']['children'],
            $this->getDependencyField(220)
        );

        // add 'Dependency' button to options
        $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
        ['container_option']['children'][CustomOptions::CONTAINER_TYPE_STATIC_NAME]['children'] = array_replace_recursive(
            $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
            ['container_option']['children'][CustomOptions::CONTAINER_TYPE_STATIC_NAME]['children'],
            $this->getDependencyField(130, true)
        );
    }

    /**
     * Retrieve array of settings of 'Dependency' field.
     *
     * @param int $sortOrder
     * @param bool $additionalForGroup
     * @return array
     */
    protected function getDependencyField($sortOrder, $additionalForGroup = false)
    {
        $field[static::FIELD_DEPENDENCY] = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => Field::NAME,
                        'formElement' => Hidden::NAME,
                        'dataScope' => static::FIELD_DEPENDENCY,
                        'dataType' => Text::NAME,
                        'additionalForGroup' => $additionalForGroup,
                        'displayArea' => 'insideGroup',
                        'sortOrder' => $sortOrder,
                    ],
                ],
            ],
        ];

        return $field;
    }

    /**
     * Add 'dependency_type' field to options.
     */
    protected function addDependencyType()
    {
        $groupCustomOptionsName = CustomOptions::GROUP_CUSTOM_OPTIONS_NAME;
        $optionContainerName = CustomOptions::CONTAINER_OPTION;

        // add mageworx_option_type_id to option values
        $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
        [$optionContainerName]['children']['values']['children']['record']['children'] = array_replace_recursive(
            $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
            [$optionContainerName]['children']['values']['children']['record']['children'],
            $this->getDependencyType(240)
        );
    }

    /**
     * Get mageworx_option_type_id field config .
     * @param $sortOrder
     * @return array
     */
    protected function getDependencyType($sortOrder)
    {
        $field[static::DEPENDENCY_TYPE] = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => Field::NAME,
                        'formElement' => Hidden::NAME,
                        'dataScope' => static::DEPENDENCY_TYPE,
                        'dataType' => Text::NAME,
                        'displayArea' => 'insideGroup',
                        'sortOrder' => $sortOrder,
                    ],
                ],
            ],
        ];

        return $field;
    }

    /**
     * Add 'option_title_id' field to options.
     */
    protected function addOptionTitleId()
    {
        $groupCustomOptionsName = CustomOptions::GROUP_CUSTOM_OPTIONS_NAME;
        $optionContainerName = CustomOptions::CONTAINER_OPTION;
        $commonOptionContainerName = CustomOptions::CONTAINER_COMMON_NAME;

        //add option_title_id to options
        $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
        [$optionContainerName]['children'][$commonOptionContainerName]['children'] = array_replace_recursive(
            $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
            [$optionContainerName]['children'][$commonOptionContainerName]['children'],
            $this->getOptionTitleId(21)
        );
    }

    /**
     * Get 'option_title_id' field config .
     * @param $sortOrder
     */
    protected function getOptionTitleId($sortOrder)
    {
        $field[static::FIELD_OPTION_TITLE_ID] = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => 'Title ID',
                        'componentType' => Field::NAME,
                        'formElement' => Input::NAME,
                        'dataScope' => static::FIELD_OPTION_TITLE_ID,
                        'dataType' => Text::NAME,
                        'fit' => true,
                        'sortOrder' => $sortOrder,
                    ],
                ],
            ],
        ];

        return $field;
    }

    /**
     * Add 'option_type_title_id' field to options.
     */
    protected function addOptionTypeTitleId()
    {
        $groupCustomOptionsName = CustomOptions::GROUP_CUSTOM_OPTIONS_NAME;
        $optionContainerName = CustomOptions::CONTAINER_OPTION;

        //add option_type_title_id to option values
        $titleConfig = $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
        [$optionContainerName]['children']['values']['children']['record']['children']['title'];
        unset($this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
            [$optionContainerName]['children']['values']['children']['record']['children']['title']);
        $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
        [$optionContainerName]['children']['values']['children']['record']['children'] = array_replace_recursive(
            ['title' => $titleConfig],
            $this->getOptionTypeTitleId(11),
            $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
            [$optionContainerName]['children']['values']['children']['record']['children']
        );
    }

    /**
     * Get 'option_type_title_id' field config .
     * @param $sortOrder
     */
    protected function getOptionTypeTitleId($sortOrder)
    {
        $field[static::FIELD_OPTION_TYPE_TITLE_ID] = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => 'Title ID',
                        'componentType' => Field::NAME,
                        'formElement' => Input::NAME,
                        'dataScope' => static::FIELD_OPTION_TYPE_TITLE_ID,
                        'dataType' => Text::NAME,
                        'sortOrder' => $sortOrder,
                    ],
                ],
            ],
        ];

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
