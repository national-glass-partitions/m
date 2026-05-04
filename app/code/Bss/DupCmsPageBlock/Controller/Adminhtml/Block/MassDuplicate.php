<?php
/**
 * BSS Commerce Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://bsscommerce.com/Bss-Commerce-License.txt
 *
 * @category   BSS
 * @package    Bss_DupCmsPageBlock
 * @author     Extension Team
 * @copyright  Copyright (c) 2017-2018 BSS Commerce Co. ( http://bsscommerce.com )
 * @license    http://bsscommerce.com/Bss-Commerce-License.txt
 */
namespace Bss\DupCmsPageBlock\Controller\Adminhtml\Block;

use Magento\Framework\Controller\ResultFactory;

class MassDuplicate extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Bss_DupCmsPageBlock::block_duplicate';

    /**
     * @var \Magento\Ui\Component\MassAction\Filter
     */
    protected $filter;

    /**
     * @var \Magento\Cms\Model\ResourceModel\Block\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var \Magento\Cms\Model\BlockFactory
     */
    protected $blockModel;

    /**
     * @var \Magento\Cms\Model\BlockRepository
     */
    protected $blockRepository;

    /**
     * @var \Bss\DupCmsPageBlock\Helper\Data
     */
    protected $helperBss;

    /**
     * MassDuplicate constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Ui\Component\MassAction\Filter $filter
     * @param \Magento\Cms\Model\ResourceModel\Block\CollectionFactory $collectionFactory
     * @param \Magento\Cms\Model\BlockFactory $blockModel
     * @param \Magento\Cms\Model\BlockRepository $blockRepository
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Ui\Component\MassAction\Filter $filter,
        \Bss\DupCmsPageBlock\Helper\Data $helperBss,
        \Magento\Cms\Model\ResourceModel\Block\CollectionFactory $collectionFactory,
        \Magento\Cms\Model\BlockFactory $blockModel,
        \Magento\Cms\Model\BlockRepository $blockRepository
    ) {
        $this->blockModel = $blockModel;
        $this->helperBss = $helperBss;
        $this->collectionFactory = $collectionFactory;
        $this->blockRepository = $blockRepository;
        $this->filter = $filter;
        parent::__construct($context);
    }

    /**
     * @param $block
     */
    private function getBlock($block)
    {
        try {
            $block->save();
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__("This block can not be duplicated"));
        }
    }
    /**
     * Duplicate CMS block
     *
     * @return \Magento\Framework\Controller\ResultInterface
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $collectionSize = $collection->getSize();

        foreach ($collection as $block) {
            $id = $block->getId();
            try {
                $model = $this->blockRepository->getById($id);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $model = null;
            }
            $duplicateBlock = $this->blockModel->create();
            $cmsBlockData = $this->helperBss->duplicate($id, $model, 'block');

            if (!$cmsBlockData) {
                $this->messageManager->addErrorMessage(__('This block no longer exists.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }

            $duplicateBlock->setData($cmsBlockData);
            $this->getBlock($duplicateBlock);
        }

        $this->messageManager->addSuccessMessage(__('A total of %1 record(s) have been duplicated.', $collectionSize));

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('cms/block/index');
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Bss_DupCmsPageBlock::duplicate_cmsblock_mass');
    }
}
