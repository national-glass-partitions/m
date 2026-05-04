<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionTemplates\Plugin;

use Magento\Catalog\Api\Data\ProductCustomOptionValuesInterfaceFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Catalog\Api\Data\ProductCustomOptionValuesInterface;

class OptionValueFactoryResolver
{
    /**
     * @var HttpRequest
     */
    protected $request;

    /**
     * Object Manager instance
     *
     * @var ObjectManagerInterface
     */
    protected $objectManager = null;

    /**
     * Product instance name to create
     *
     * @var string
     */
    protected $instanceName = null;

    /**
     * Group instance name to create
     *
     * @var string
     */
    protected $groupInstanceName = null;

    /**
     * Factory constructor
     *
     * @param HttpRequest $request
     * @param ObjectManagerInterface $objectManager
     * @param string $instanceName
     * @param string $groupInstanceName
     */
    public function __construct(
        HttpRequest $request,
        ObjectManagerInterface $objectManager,
        $instanceName = '\\Magento\\Catalog\\Api\\Data\\ProductCustomOptionValuesInterface',
        $groupInstanceName = '\\MageWorx\\OptionTemplates\\Model\\Group\\Option\\Value'
    ) {
        $this->request           = $request;
        $this->objectManager     = $objectManager;
        $this->instanceName      = $instanceName;
        $this->groupInstanceName = $groupInstanceName;
    }

    /**
     * @param ProductCustomOptionValuesInterfaceFactory $subject
     * @param \Closure $proceed
     * @param array $data
     * @return ProductCustomOptionValuesInterface|
     */
    public function aroundCreate(
        ProductCustomOptionValuesInterfaceFactory $subject,
        \Closure $proceed,
        array $data = array()
    ) {
        if ($this->request->getParam('mageworx_optiontemplates_group')
            || $this->request->getActionName() === 'importMageOne'
            || $this->request->getActionName() === 'importTemplateMageTwo'
        ) {
            return $this->objectManager->create($this->groupInstanceName, $data);
        }
        return $proceed();
    }
}