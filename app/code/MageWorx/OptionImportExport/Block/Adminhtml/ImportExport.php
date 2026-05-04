<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionImportExport\Block\Adminhtml;

use MageWorx\OptionBase\Helper\System as SystemHelper;
use MageWorx\OptionImportExport\Model\Config\Source\BeforeImportSystemStatus;
use MageWorx\OptionImportExport\Model\Config\Source\MigrationMode;

class ImportExport extends \Magento\Backend\Block\Widget
{
    /**
     * @var string
     */
    protected $_template = 'import_export.phtml';

    /**
     * @var SystemHelper
     */
    protected $systemHelper;

    /**
     * @param SystemHelper $systemHelper
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(
        SystemHelper $systemHelper,
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ) {
        $this->systemHelper = $systemHelper;
        parent::__construct($context, $data);
        $this->setUseContainer(true);
        $this->setImportFromStoreIds($this->_backendSession->getStoreIds());
        $this->setImportFromCustomerGroupIds($this->_backendSession->getCustomerGroupIds());
        $this->setFileMagentoVersion($this->_backendSession->getFileMagentoVersion());
        $this->setImportMode($this->_backendSession->getImportMode());
        $this->setMissingProducts($this->_backendSession->getMissingProducts());
        $this->setAssignedProducts($this->_backendSession->getAssignedProducts());
        $this->setIsOptionsFreeMagentoMode($this->_backendSession->getBeforeImportSystemStatus() === BeforeImportSystemStatus::BEFORE_IMPORT_SYSTEM_STATUS_OPTIONS_FREE);
        $this->setIsNoIntersectionMode($this->_backendSession->getBeforeImportSystemStatus() === BeforeImportSystemStatus::BEFORE_IMPORT_SYSTEM_STATUS_NO_INTERSECTION);
        $this->setIsProductIntersectionMode($this->_backendSession->getBeforeImportSystemStatus() === BeforeImportSystemStatus::BEFORE_IMPORT_SYSTEM_STATUS_INTERSECTION);
        $this->_backendSession->setStoreIds([]);
        $this->_backendSession->setCustomerGroupIds([]);
        $this->_backendSession->setAssignedProducts([]);
        $this->_backendSession->setCanSkipTemplatesApplying(false);
        $this->_backendSession->setBeforeImportSystemStatus('');
        $this->_backendSession->setImportMode('');
    }

    /**
     * @return array
     */
    public function getCustomerGroups()
    {
        return $this->systemHelper->getCustomerGroups();
    }

    /**
     * @return array
     */
    public function getStores()
    {
        return $this->systemHelper->getStores();
    }

    /**
     * @return array
     */
    public function getActionUrls()
    {
        $urls = [];

        $urls['m1-action-url'] = $this->getUrl('mageworx_optionimportexport/importExport/importMageOne');
        $urls['m2-action-url'] = $this->getUrl('mageworx_optionimportexport/importExport/importTemplateMageTwo');

        return $urls;
    }

    /**
     * @return bool
     */
    public function hasMissingProducts()
    {
        return !empty($this->_backendSession->getMissingSkus());
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getLabelDeleteAllOptions()
    {
        return MigrationMode::getMigrationModeDeleteAllOptionsLabel();
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getLabelDeleteOptionsOnIntersectingProducts()
    {
        return MigrationMode::getMigrationModeDeleteOptionsOnIntersectingProductsLabel();
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getLabelAddOptionsToTheEnd()
    {
        return MigrationMode::getMigrationModeAddOptionsToTheEndLabel();
    }
}
