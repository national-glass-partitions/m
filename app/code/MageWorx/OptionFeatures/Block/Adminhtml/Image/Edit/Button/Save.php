<?php
/**
 * Copyright © 2017 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionFeatures\Block\Adminhtml\Image\Edit\Button;

use Magento\Catalog\Block\Adminhtml\Product\Edit\Button\Generic;

class Save extends Generic
{
    /**
     * {@inheritdoc}
     */
    public function getButtonData()
    {
        $form = 'product_form';
        if ($this->context->getRequestParam('formName')) {
            $form = $this->context->getRequestParam('formName');
        }

        return [
            'label' => __('Save & Close'),
            'class' => 'save primary',
            'data_attribute' => [
                'mage-init' => [
                    'buttonAdapter' => [
                        'actions' => [
                            [
                                'targetName' => $form . '.' . $form . '.option_value_images_modal.content',
                                'actionName' => 'saveImagesData',
                            ],
                            [
                                'targetName' => $form . '.' . $form . '.option_value_images_modal',
                                'actionName' => 'closeModal',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
