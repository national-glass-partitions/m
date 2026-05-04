<?php
/**
 * Grid Regenerate Action
 *
 * @category   Scommerce
 * @package    Scommerce_Cachewarmer
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */

namespace Scommerce\CacheWarmer\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Scommerce\CacheWarmer\Helper\Data as Datahelper;

class Regenerate extends Action
{
    
    
    /**
     * @var helper
     */
    protected $_helperData;
   
    /**
     * @param Context $context
     * @param Data $helper
     */
    public function __construct(Context $context, Datahelper $helper)
    {
        $this->_helperData = $helper;
        parent::__construct($context);
    } 
    
    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Scommerce_Cachewarmer::cachewarmer_regenerate');
    }

    /**
     * Delete action
     *
     * @return void
     */
    public function execute()
    {
        // check if we know what should be deleted
        $id = $this->getRequest()->getParam('entity_id');
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($id) {
            try { 
                $this->_helperData->regenerateCache($id);
                // display success message
                $this->messageManager->addSuccess(__('Cache has regenerated.'));
                // go to grid
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                // display error message
                $this->messageManager->addError($e->getMessage());
                // go back to edit form
                return $resultRedirect->setPath('*/*/edit', ['page_id' => $id]);
            }
        }
        // display error message
        $this->messageManager->addError(__('We can\'t find a data to edit'));
        // go to grid
        return $resultRedirect->setPath('*/*/');
    }
}