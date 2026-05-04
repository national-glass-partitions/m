<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\OptionFeatures\Controller\Adminhtml\Form\Image;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Serialize\Serializer\Json as Serializer;
use Magento\Framework\View\Result\PageFactory;

class Load extends Action
{
    /**
     * Page factory
     *
     * @var PageFactory
     */
    protected PageFactory $pageFactory;

    /**
     * Raw factory
     *
     * @var RawFactory
     */
    protected RawFactory $rawFactory;
    protected Serializer $serializer;

    /**
     * Load constructor.
     *
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param RawFactory $rawFactory
     * @param Serializer $serializer
     */
    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        RawFactory $rawFactory,
        Serializer $serializer
    ) {
        $this->rawFactory  = $rawFactory;
        $this->pageFactory = $pageFactory;
        $this->serializer  = $serializer;

        return parent::__construct($context);
    }

    /**
     * Render block form
     *
     * @return Raw
     * @throws \Exception
     */
    public function execute()
    {
        try {
            $response = [
                'result' => $this->getResultHtml(),
                'status' => true,
            ];
        } catch (\Exception $e) {
            $response = [
                'error' => $e->getMessage(),
                'status' => false,
            ];
        }

        /** @var  $result */
        $result = $this->rawFactory->create()->setContents($this->serializer->serialize($response));

        return $result;
    }

    /**
     * @return string
     */
    protected function getResultHtml()
    {
        $resultPage = $this->pageFactory->create();
        $resultPage->addHandle('option_value_images');

        return $resultPage->getLayout()->renderElement('content');
    }
}
