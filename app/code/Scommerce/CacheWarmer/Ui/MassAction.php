<?php
/**
 * Scommerce CacheWarmer UI component
 *
 * @category   Scommerce
 * @package    Scommerce_CacheWarmer
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */
namespace Scommerce\CacheWarmer\Ui;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\AuthorizationInterface;
use Scommerce\CacheWarmer\Helper\Data;
use Magento\Ui\Component\MassAction as CacheWarmerMassAction;

class MassAction extends CacheWarmerMassAction
{

    /**
     * @var authorization
     */
    private $_authorization;
    
    /**
     * @var dataHelper
     */
    protected $_helper;
    
    /**
     * __construct
     *
     * @param ContextInterface $context
     * @param AuthorizationInterface $authorization
     * @param Data $helper
     * @param type $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        AuthorizationInterface $authorization,
        Data $helper,
        array $components = [],
        array $data = []
    ) {
        $this->_authorization = $authorization;
        $this->_helper = $helper;
        parent::__construct($context, $components, $data);
    }
    /**
     * Prepare
     */
    public function prepare()
    {

        parent::prepare();
        $config = $this->getConfiguration();

            $allowedActions = [];
        foreach ($config['actions'] as $action) {           
                $allowedActions[] = $action;
        }
            $config['actions'] = $allowedActions;

            $this->setData('config', (array) $config);
    }
}
