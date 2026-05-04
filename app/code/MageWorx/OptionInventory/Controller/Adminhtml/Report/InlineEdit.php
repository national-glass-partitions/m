<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionInventory\Controller\Adminhtml\Report;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use \Magento\Catalog\Model\Product\Option\Value as OptionValueModel;

class InlineEdit extends Action
{
    protected JsonFactory $jsonFactory;
    protected ObjectManagerInterface $objectManager;

    /**
     * InlineEdit constructor.
     *
     * @param JsonFactory $jsonFactory
     * @param Registry $registry
     * @param Context $context
     */
    public function __construct(
        JsonFactory $jsonFactory,
        Registry $registry,
        Context $context
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->objectManager = $context->getObjectManager();
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->jsonFactory->create();
        $error = false;
        $messages = [];

        $postItems = $this->getRequest()->getParam('items', []);
        if (!($this->getRequest()->getParam('isAjax') && count($postItems))) {
            return $resultJson->setData([
                'messages' => [__('Please correct the data sent.')],
                'error' => true,
            ]);
        }

        foreach (array_keys($postItems) as $optionValueId) {
            $optionValue = $this->objectManager
                ->create('\Magento\Catalog\Model\Product\Option\Value')
                ->load($optionValueId);

            try {
                $optionValueData = $this->filterData($postItems[$optionValueId]);
                $optionValue->addData($optionValueData);

                $optionValue->save();
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $messages[] = $this->getErrorWithOptionValueId($optionValue, $e->getMessage());
                $error = true;
            } catch (\RuntimeException $e) {
                $messages[] = $this->getErrorWithOptionValueId($optionValue, $e->getMessage());
                $error = true;
            } catch (\Exception $e) {
                $messages[] = $this->getErrorWithOptionValueId(
                    $optionValue,
                    __('Something went wrong while saving the option value.')
                );
                $error = true;
            }
        }

        return $resultJson->setData([
            'messages' => $messages,
            'error' => $error
        ]);
    }

    /**
     * Add option value id to error message
     *
     * @param OptionValueModel $optionValue
     * @param string $errorText
     * @return string
     */
    protected function getErrorWithOptionValueId(OptionValueModel $optionValue, $errorText): string
    {
        return '[Option Value ID: ' . $optionValue->getId() . '] ' . $errorText;
    }

    /**
     *
     * @param array $data
     * @return array
     */
    protected function filterData($data)
    {
        return $data;
    }
}
