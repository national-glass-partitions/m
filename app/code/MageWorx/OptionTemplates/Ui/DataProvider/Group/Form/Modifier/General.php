<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionTemplates\Ui\DataProvider\Group\Form\Modifier;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Framework\UrlInterface;
use Magento\Ui\Component\Form\Element\Checkbox;
use Magento\Ui\Component\Form\Element\DataType\Number;
use Magento\Ui\Component\Form\Element\DataType\Text;
use Magento\Ui\Component\Form\Element\Hidden;
use Magento\Ui\Component\Form\Element\Input;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\Component\Form\Fieldset;
use MageWorx\OptionTemplates\Model\Group\Source\AssignType as AssignTypeOptions;

/**
 * Data provider for main panel of product page
 */
class General extends AbstractModifier
{

    const FIELD_GROUP_NAME = 'group';
    const FIELD_ENTITY_ID_NAME = 'group_id';
    const FIELD_TITLE_NAME = 'title';
    const FIELD_IS_ACTIVE_NAME = 'is_active';

    const KEY_SUBMIT_URL = 'submit_url';

    /**
     * @var LocatorInterface
     */
    protected $locator;

    /**
     * @var ArrayManager
     */
    protected $arrayManager;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     *
     * @var AssignTypeOptions
     */
    protected $assignTypeOptions;

    /**
     * @var array
     */
    protected $meta = [];

    /**
     * @param LocatorInterface $locator
     * @param ArrayManager $arrayManager
     * @param UrlInterface $urlBuilder
     * @param AssignTypeOptions $assignTypeOptions
     */
    public function __construct(
        LocatorInterface $locator,
        ArrayManager $arrayManager,
        UrlInterface $urlBuilder,
        AssignTypeOptions $assignTypeOptions
    ) {
        $this->locator = $locator;
        $this->arrayManager = $arrayManager;
        $this->urlBuilder = $urlBuilder;
        $this->assignTypeOptions = $assignTypeOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {

        // Add submit (save) url to the config
        $actionParameters = [];
        $submitUrl = $this->urlBuilder->getUrl('mageworx_optiontemplates/group/save', $actionParameters);
        $data = array_replace_recursive(
            $data,
            [
                'config' => [
                    self::KEY_SUBMIT_URL => $submitUrl,
                ]
            ]
        );

        // Add a group data if the group exists
        /** @var \MageWorx\OptionTemplates\Model\Group $group */
        $group = $this->locator->getProduct();
        $group->setData('assign_type', (string)AssignTypeOptions::ASSIGN_BY_GRID);
        if ($group && $group->getId()) {
            return array_replace_recursive(
                $data,
                [
                    $group->getId() => [
                        static::DATA_SOURCE_DEFAULT => $group->getData(),
                    ],
                ]
            );
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        $this->meta = $meta;
        $this->buildMainFields();

        return $this->meta;
    }

    protected function buildMainFields()
    {

        $this->meta[static::FIELD_GROUP_NAME] = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => Fieldset::NAME,
                        'label' => __('%1', 'Options Template Settings'),
                        'collapsible' => true,
                        'opened' => true,
                        'dataScope' => self::DATA_SCOPE_GROUP,
                        'sortOrder' => 10,
                    ],
                ],
            ],
            'children' => [
                static::FIELD_ENTITY_ID_NAME => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'label' => __('Id'),
                                'componentType' => Field::NAME,
                                'formElement' => Hidden::NAME,
                                'dataScope' => static::FIELD_ENTITY_ID_NAME,
                                'dataType' => Number::NAME,
                                'sortOrder' => 0,
                            ],
                        ],
                    ],
                ],
                static::FIELD_TITLE_NAME => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'label' => __('Title'),
                                'componentType' => Field::NAME,
                                'formElement' => Input::NAME,
                                'dataScope' => static::FIELD_TITLE_NAME,
                                'dataType' => Text::NAME,
                                'validation' => [
                                    'required-entry' => true,
                                ],
                                'sortOrder' => 10,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return $this->meta;
    }

    /**
     * Retrieve filtered by same template type assign options
     *
     * @return array
     */
    protected function getAssignTypes()
    {
        return $this->assignTypeOptions->toOptionArray();
    }
}
