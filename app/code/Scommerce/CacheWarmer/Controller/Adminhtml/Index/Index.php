<?php
/**
 * Grid Index Action
 *
 * @category   Scommerce
 * @package    Scommerce_Cachewarmer
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */

namespace Scommerce\CacheWarmer\Controller\Adminhtml\Index;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\App\Action;

class Index extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }
    
    /**
     * Check the permission to run it
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Scommerce_CacheWarmer::cachewarmer_manage');
    }
    
    
    /**
     * CacheWarmer List action
     *
     * @return string
     */
    public function execute()
    { 
        /** @var PageFactory $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu(
            'Scommerce_CacheWarmer::cachewarmer_manage'
        )->addBreadcrumb(
            __('Cachewarmer'),
            __('Cachewarmer')
        )->addBreadcrumb(
            __('Manage Cache Warmer'),
            __('Manage Cache Warmer')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Cache Warmer'));
        return $resultPage;
    }
}
