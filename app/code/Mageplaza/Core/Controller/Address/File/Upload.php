<?php
namespace Mageplaza\Core\Controller\Address\File;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\Action;

class Upload extends Action implements HttpPostActionInterface
{
    private $resultJsonFactory;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
    }

    public function execute()
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/sessionreaper_blocked.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('Blocked upload attempt from IP: ' . $this->getRequest()->getClientIp());

        $result = $this->resultJsonFactory->create();
        return $result->setData([
            'error' => 'This endpoint has been disabled for security reasons',
            'errorcode' => 403
        ])->setHttpResponseCode(403);
    }
}
